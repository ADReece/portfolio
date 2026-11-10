<?php

namespace Tests\Feature\CustomDomains;

class InternalEndpointTest extends CustomDomainTestCase
{
    private function call_(string $url = 'http://web-abc123:8080/internal/custom-domains', ?string $token = 'sync-secret', array $headers = [])
    {
        return $this->getJson($url, ($token ? ['Authorization' => 'Bearer '.$token] : []) + $headers);
    }

    public function test_lists_live_hostnames_with_their_usernames(): void
    {
        $this->liveDomain($this->photographer());
        $this->liveDomain($this->photographer('ava'), 'photos.ava.co.uk', ['include_www' => false]);
        $this->liveDomain($this->photographer('pending'), 'pending.com', ['status' => 'pending', 'verified_at' => null]);
        $this->liveDomain($this->photographer('broke', 'free'), 'free-plan.com');

        $this->call_()->assertOk()
            ->assertExactJson([
                'domains' => ['photos.ava.co.uk', 'stephshoots.com', 'www.stephshoots.com'],
                'items' => [
                    ['domain' => 'photos.ava.co.uk', 'username' => 'ava', 'primary' => true, 'redirect_to' => null],
                    ['domain' => 'stephshoots.com', 'username' => 'stephshoots', 'primary' => true, 'redirect_to' => null],
                    ['domain' => 'www.stephshoots.com', 'username' => 'stephshoots', 'primary' => false, 'redirect_to' => 'stephshoots.com'],
                ],
            ]);
    }

    public function test_auth_and_host_checks(): void
    {
        $this->call_(token: null)->assertStatus(401);
        $this->call_(token: 'wrong')->assertStatus(401);
        $this->call_('https://portfolio.ascend-digital.co.uk/internal/custom-domains')->assertOk();

        // Never on a customer's domain (or an unknown one).
        $this->liveDomain($this->photographer());
        $this->call_('https://stephshoots.com/internal/custom-domains')->assertNotFound();
        $this->call_('https://evil.example.org/internal/custom-domains')->assertNotFound();

        config(['custom_domains.sync_token' => null]);
        $this->call_()->assertNotFound();
    }

    public function test_no_session_cookie_or_csrf(): void
    {
        $this->call_()->assertOk()->assertCookieMissing(config('session.cookie'));
    }
}
