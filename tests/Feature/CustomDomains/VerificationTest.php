<?php

namespace Tests\Feature\CustomDomains;

use App\CustomDomains\DomainVerifier;
use App\CustomDomains\Hostname;
use App\Models\CustomDomain;
use Carbon\CarbonImmutable;

class VerificationTest extends CustomDomainTestCase
{
    private function pending(string $host = 'stephshoots.com', array $attrs = []): CustomDomain
    {
        return CustomDomain::factory()->create(['user_id' => $this->photographer()->id, 'hostname' => $host] + $attrs);
    }

    public function test_hostnames_are_normalised_and_platform_or_bad_ones_refused(): void
    {
        $this->assertSame(['hostname' => 'stephshoots.com', 'had_www' => true], Hostname::normalise(' https://WWW.StephShoots.com/about '));
        $this->assertSame('xn--stphshoots-c7a.com', Hostname::normalise('stéphshoots.com')['hostname']);
        foreach (['2.28.38.210', 'localhost', 'portfolio.ascend-digital.co.uk', 'evil.ascend-digital.co.uk', 'nodot', 'bad_host.com', '-x.com', 'x.test'] as $bad) {
            $this->assertNull(Hostname::normalise($bad), $bad);
        }
    }

    public function test_verifies_when_txt_and_a_records_are_right(): void
    {
        $domain = $this->pending();
        $this->pointDns($domain);

        app(DomainVerifier::class)->verify($domain);

        $this->assertSame('verified', $domain->fresh()->status);
        $this->assertNotNull($domain->fresh()->verified_at);
    }

    public function test_cname_to_the_platform_counts_as_pointing_here(): void
    {
        $domain = $this->pending('photos.steph.co.uk', ['include_www' => false]);
        $this->dns->txt[$domain->txtRecordName()] = [$domain->verification_token];
        $this->dns->cname['photos.steph.co.uk'] = ['portfolio.ascend-digital.co.uk'];
        $this->dns->a['photos.steph.co.uk'] = ['2.28.38.210'];

        $this->assertSame([], app(DomainVerifier::class)->problems($domain));
    }

    public function test_explains_each_problem(): void
    {
        $domain = $this->pending();
        $this->dns->a['stephshoots.com'] = ['1.2.3.4'];
        $this->dns->a['www.stephshoots.com'] = ['2.28.38.210'];
        $this->dns->aaaa['www.stephshoots.com'] = ['2001:db8::1'];

        $problems = app(DomainVerifier::class)->problems($domain);

        $this->assertCount(3, $problems);
        $this->assertStringContainsString('TXT record _portfolio-verify.stephshoots.com', $problems[0]);
        $this->assertStringContainsString('stephshoots.com points somewhere else (1.2.3.4)', $problems[1]);
        $this->assertStringContainsString('AAAA', $problems[2]);
    }

    public function test_pending_domain_fails_after_a_week(): void
    {
        $domain = $this->pending();
        $verifier = app(DomainVerifier::class);

        $verifier->verify($domain, CarbonImmutable::now()->addDays(2));
        $this->assertSame('pending', $domain->status);
        $this->assertNotNull($domain->failure_reason);

        $verifier->verify($domain, CarbonImmutable::now()->addDays(8));
        $this->assertSame('failed', $domain->status);
    }

    public function test_live_domain_whose_dns_moves_away_goes_offline_after_the_grace_period(): void
    {
        $domain = $this->liveDomain($this->photographer('ava'), 'avaphoto.com');
        $verifier = app(DomainVerifier::class);
        $now = CarbonImmutable::now();

        $verifier->verify($domain, $now);
        $this->assertSame('verified', $domain->status, 'still served while the owner fixes it');
        $this->assertNotNull($domain->failing_since);
        $this->getJson('http://web-1:8080/internal/custom-domains', ['Authorization' => 'Bearer sync-secret'])->assertJsonFragment(['avaphoto.com']);

        $verifier->verify($domain, $now->addHours(49));
        $this->assertSame('failed', $domain->status);
        $this->getJson('http://web-1:8080/internal/custom-domains', ['Authorization' => 'Bearer sync-secret'])->assertJsonMissing(['avaphoto.com']);

        // Fixing DNS brings it back.
        $this->pointDns($domain);
        $verifier->verify($domain, $now->addHours(50));
        $this->assertSame('verified', $domain->status);
        $this->assertNull($domain->failing_since);
    }

    public function test_scheduled_command_checks_pending_domains(): void
    {
        $domain = $this->pending();
        $this->pointDns($domain);

        $this->artisan('custom-domains:verify --pending')->expectsOutputToContain('stephshoots.com: pending -> verified')->assertSuccessful();
    }
}
