<?php

namespace Tests\Feature\CustomDomains;

use App\Models\CustomDomain;

class ConnectCommandTest extends CustomDomainTestCase
{
    private function pointA(): void
    {
        $this->dns->a['stephshoots.com'] = ['2.28.38.210'];
        $this->dns->a['www.stephshoots.com'] = ['2.28.38.210'];
    }

    public function test_trusted_connect_goes_live_without_a_txt_record(): void
    {
        $this->photographer();
        $this->pointA();

        $this->artisan('custom-domains:connect stephshoots stephshoots.com --trusted')
            ->expectsOutputToContain('stephshoots.com is live for @stephshoots')
            ->assertSuccessful();

        $domain = CustomDomain::sole();
        $this->assertTrue($domain->isVerified());
        $this->assertTrue($domain->ownership_trusted);
        $this->getJson('http://web-1:8080/internal/custom-domains', ['Authorization' => 'Bearer sync-secret'])
            ->assertJsonPath('domains', ['stephshoots.com', 'www.stephshoots.com']);
        $this->get('https://stephshoots.com/')->assertOk();
    }

    public function test_dns_must_still_point_here_even_when_trusted(): void
    {
        $this->photographer();
        $this->dns->a['stephshoots.com'] = ['9.9.9.9'];

        $this->artisan('custom-domains:connect stephshoots stephshoots.com --trusted')
            ->expectsOutputToContain('points somewhere else (9.9.9.9)')
            ->assertFailed();
        $this->assertSame('pending', CustomDomain::sole()->status);
    }

    public function test_untrusted_connect_needs_the_txt_record(): void
    {
        $this->photographer();
        $this->pointA();

        $this->artisan('custom-domains:connect stephshoots stephshoots.com')
            ->expectsOutputToContain('TXT _portfolio-verify.stephshoots.com')
            ->assertFailed();
    }

    public function test_plan_check_and_override(): void
    {
        $steph = $this->photographer('stephshoots', 'free');
        $this->pointA();

        $this->artisan('custom-domains:connect stephshoots stephshoots.com --trusted')
            ->expectsOutputToContain("plan doesn't include custom domains")->assertFailed();
        $this->assertSame(0, CustomDomain::count());

        $this->artisan('custom-domains:connect stephshoots stephshoots.com --trusted --grant-override')->assertSuccessful();
        $this->assertTrue($steph->fresh()->isFeatureOverrideActive());
    }

    public function test_refuses_unknown_users_and_someone_elses_domain(): void
    {
        $this->artisan('custom-domains:connect nobody stephshoots.com')->assertFailed();

        $this->liveDomain($this->photographer('ava'), 'stephshoots.com');
        $this->photographer();
        $this->artisan('custom-domains:connect stephshoots stephshoots.com --trusted')
            ->expectsOutputToContain('already connected to another portfolio')->assertFailed();
    }
}
