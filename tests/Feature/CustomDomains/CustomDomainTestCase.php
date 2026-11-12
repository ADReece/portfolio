<?php

namespace Tests\Feature\CustomDomains;

use App\CustomDomains\DnsResolver;
use App\Models\Collection;
use App\Models\CustomDomain;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CustomDomainTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeDns $dns;

    /** Hosts the HTTPS check reaches (their certificate is issued). */
    protected array $securedHosts = [];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://portfolio.ascend-digital.co.uk',
            'custom_domains.target_ip' => '2.28.38.210',
            'custom_domains.target_ipv6' => null,
            'custom_domains.sync_token' => 'sync-secret',
        ]);
        $this->seed(SubscriptionPlanSeeder::class);
        $this->dns = new FakeDns();
        $this->app->instance(DnsResolver::class, $this->dns);
        // The HTTPS check (CertificateCheck): not secured unless a test says so.
        \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $host = parse_url($request->url(), PHP_URL_HOST);

            return in_array($host, $this->securedHosts, true)
                ? \Illuminate\Support\Facades\Http::response(\App\CustomDomains\CertificateCheck::token($host))
                : \Illuminate\Support\Facades\Http::response('', 404);
        });
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }

    protected function photographer(string $username = 'stephshoots', string $plan = 'photographer'): User
    {
        return User::factory()->create([
            'username' => $username,
            'subscription_plan_id' => SubscriptionPlan::where('slug', $plan)->value('id'),
        ]);
    }

    protected function liveDomain(User $user, string $host = 'stephshoots.com', array $attrs = []): CustomDomain
    {
        return CustomDomain::factory()->verified()->create(['user_id' => $user->id, 'hostname' => $host] + $attrs);
    }

    protected function publicCollection(User $user): Collection
    {
        return Collection::factory()->published()->create(['user_id' => $user->id, 'name' => 'Spring Wedding']);
    }

    /** DNS that is correct for $domain. */
    protected function pointDns(CustomDomain $domain): void
    {
        $this->dns->txt[$domain->txtRecordName()] = [$domain->verification_token];
        foreach ($domain->hostnames() as $host) {
            $this->dns->a[$host] = ['2.28.38.210'];
        }
    }
}
