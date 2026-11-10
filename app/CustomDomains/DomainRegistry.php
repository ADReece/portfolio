<?php

namespace App\CustomDomains;

use App\Models\CustomDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Which hostnames are live: verified domains whose owner's plan still
 * includes custom domains. Cached briefly; any domain change clears it.
 */
class DomainRegistry
{
    private const CACHE_KEY = 'custom-domains.live';

    /**
     * @return array<string, array{domain_id: int, user_id: string, username: string, primary: string}>
     *   keyed by every live hostname (apex and www)
     */
    public function live(): array
    {
        return Cache::remember(self::CACHE_KEY, 60, function () {
            $live = [];
            CustomDomain::query()->where('status', CustomDomain::VERIFIED)->with('user.subscriptionPlan')
                ->get()
                ->filter(fn (CustomDomain $d) => $d->user && $d->user->username && $d->user->customDomainLimit() > 0)
                ->each(function (CustomDomain $d) use (&$live) {
                    foreach ($d->hostnames() as $host) {
                        $live[$host] = [
                            'domain_id' => $d->id,
                            'user_id' => (string) $d->user_id,
                            'username' => $d->user->username,
                            'primary' => $d->primaryHostname(),
                        ];
                    }
                });

            return $live;
        });
    }

    public function find(string $host): ?array
    {
        return $this->live()[strtolower(rtrim($host, '.'))] ?? null;
    }

    /** The live primary hostname for this user's portfolio, if they have one. */
    public function primaryFor(User|string $username): ?string
    {
        $username = $username instanceof User ? $username->username : $username;

        foreach ($this->live() as $entry) {
            if (strcasecmp($entry['username'], (string) $username) === 0) {
                return $entry['primary'];
            }
        }

        return null;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** The app's own hosts, never treated as a customer's domain. */
    public static function isPlatformHost(string $host): bool
    {
        $host = strtolower(rtrim($host, '.'));
        if ($host === '' || $host === 'localhost' || ! str_contains($host, '.') || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            return true; // container names, IPs, health checks
        }
        $platform = array_merge([parse_url((string) config('app.url'), PHP_URL_HOST)], config('custom_domains.platform_hosts', []));

        return in_array($host, array_filter($platform), true);
    }
}
