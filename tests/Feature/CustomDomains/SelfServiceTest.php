<?php

namespace Tests\Feature\CustomDomains;

use App\CustomDomains\CertificateCheck;
use App\CustomDomains\DomainRegistry;
use App\Models\CustomDomain;
use Illuminate\Support\Facades\Http;

class SelfServiceTest extends CustomDomainTestCase
{
    private function securedResponse(string $host): void
    {
        $this->securedHosts[] = $host;
    }

    public function test_waiting_for_dns_then_securing_then_live(): void
    {
        $steph = $this->photographer();
        $domain = CustomDomain::factory()->create(['user_id' => $steph->id, 'hostname' => 'stephshoots.com']);

        $this->actingAs($steph)->get(route('custom-domains.index'))->assertSee('Waiting for DNS');

        $this->pointDns($domain);
        $this->actingAs($steph)->post(route('custom-domains.verify', $domain))->assertSessionHas('success');
        $this->assertNull($domain->fresh()->secured_at, 'no certificate yet');
        $this->actingAs($steph)->get(route('custom-domains.index'))->assertSee('Securing')->assertSee('security certificate');

        // The sync job routes it and Let's Encrypt issues the certificate; the scheduler notices.
        $this->securedResponse('stephshoots.com');
        $this->artisan('custom-domains:verify --securing')->expectsOutputToContain('stephshoots.com: live over HTTPS')->assertSuccessful();
        $this->assertNotNull($domain->fresh()->secured_at);
        $this->actingAs($steph)->get(route('custom-domains.index'))->assertSee('Live')->assertDontSee('Waiting for DNS');
    }

    public function test_https_check_answers_with_a_per_host_token(): void
    {
        $this->get('http://portfolio.ascend-digital.co.uk/.well-known/portfolio-domain')
            ->assertOk()->assertSee(CertificateCheck::token('portfolio.ascend-digital.co.uk'));

        $steph = $this->photographer();
        $this->liveDomain($steph);
        $this->get('https://stephshoots.com/.well-known/portfolio-domain')
            ->assertOk()->assertSeeText(CertificateCheck::token('stephshoots.com'));
        $this->assertNotSame(CertificateCheck::token('stephshoots.com'), CertificateCheck::token('www.stephshoots.com'));
    }

    public function test_cloudflare_proxied_records_are_explained(): void
    {
        $steph = $this->photographer();
        $domain = CustomDomain::factory()->create(['user_id' => $steph->id, 'hostname' => 'stephshoots.com', 'include_www' => false]);
        $this->dns->txt[$domain->txtRecordName()] = [$domain->verification_token];
        $this->dns->a['stephshoots.com'] = ['104.21.32.1', '172.67.140.2'];

        $this->actingAs($steph)->post(route('custom-domains.verify', $domain))->assertSessionHas('error');
        $this->assertStringContainsString('grey cloud', $domain->fresh()->failure_reason);
        $this->actingAs($steph)->get(route('custom-domains.index'))->assertSee('Using Cloudflare?');
    }

    public function test_hosts_the_server_refuses_cannot_be_added(): void
    {
        $steph = $this->photographer();
        foreach (['shop.ascend-customwear.co.uk', 'svtcounselling.co.uk', '1.2.3.4.sslip.io', 'portfolio.ascend-digital.co.uk'] as $host) {
            $this->actingAs($steph)->post(route('custom-domains.store'), ['hostname' => $host])->assertSessionHasErrors('hostname');
        }
        $this->assertSame(0, CustomDomain::count());
    }

    public function test_the_server_limit_is_respected(): void
    {
        config(['custom_domains.max_hostnames' => 4]);
        $a = $this->photographer('aaa');
        $this->liveDomain($a, 'aaa.com'); // aaa.com + www
        $b = $this->photographer('bbb');
        $this->liveDomain($b, 'bbb.com', ['verified_at' => now()->addMinute()]); // bbb.com + www

        $this->actingAs($this->photographer('ccc'))->post(route('custom-domains.store'), ['hostname' => 'ccc.com'])
            ->assertSessionHasErrors('hostname');

        config(['custom_domains.max_hostnames' => 3]);
        app(DomainRegistry::class)->forget();
        $this->assertSame(['aaa.com', 'www.aaa.com'], array_keys(app(DomainRegistry::class)->live()), 'the oldest stay; the server never gets more than the limit');
    }

    public function test_a_domain_dropped_for_moved_dns_needs_securing_again(): void
    {
        $steph = $this->photographer();
        $domain = $this->liveDomain($steph, 'stephshoots.com', ['secured_at' => now(), 'include_www' => false]);
        $this->dns->txt[$domain->txtRecordName()] = [$domain->verification_token];
        $this->dns->a['stephshoots.com'] = ['9.9.9.9'];

        $this->travel(1)->hours();
        $this->artisan('custom-domains:verify --verified');
        $this->assertNotNull($domain->fresh()->secured_at, 'still served during the grace period');
        $this->travel(49)->hours();
        $this->artisan('custom-domains:verify --failing');
        $this->assertSame(CustomDomain::FAILED, $domain->fresh()->status);
        $this->assertNull($domain->fresh()->secured_at);
    }
}
