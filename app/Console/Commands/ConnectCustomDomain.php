<?php

namespace App\Console\Commands;

use App\CustomDomains\DomainRegistry;
use App\CustomDomains\DomainVerifier;
use App\CustomDomains\Hostname;
use App\Models\CustomDomain;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Connects a domain to a photographer's portfolio from the server, for admins
 * (e.g. php artisan custom-domains:connect stephshoots stephshoots.com --trusted).
 * Does what the photographer would do under Your domain, then checks DNS.
 */
class ConnectCustomDomain extends Command
{
    protected $signature = 'custom-domains:connect
        {username : The portfolio\'s username, without the @}
        {hostname : The domain, e.g. stephshoots.com}
        {--trusted : An admin vouches for ownership, so no TXT record is needed (DNS must still point here)}
        {--grant-override : Give the account a feature override if its plan doesn\'t include custom domains}
        {--no-www : Don\'t also serve www.<domain>}
        {--primary-www : Make www.<domain> the main address}';

    protected $description = 'Connect a custom domain to a portfolio and verify its DNS';

    public function handle(DomainVerifier $verifier, DomainRegistry $registry): int
    {
        $user = User::where('username', ltrim($this->argument('username'), '@'))->first();
        if (! $user) {
            $this->error('No portfolio with that username.');

            return self::FAILURE;
        }

        $parsed = Hostname::normalise($this->argument('hostname'));
        if (! $parsed) {
            $this->error('That isn\'t a domain that can be connected.');

            return self::FAILURE;
        }
        $host = $parsed['hostname'];

        if ($user->customDomainLimit() === 0) {
            if (! $this->option('grant-override')) {
                $this->error("@{$user->username}'s plan doesn't include custom domains. Upgrade it, or re-run with --grant-override.");

                return self::FAILURE;
            }
            $user->forceFill(['feature_override' => true, 'feature_override_expires_at' => null])->save();
            $this->line("Gave @{$user->username} a feature override (all features, no expiry). Remove it in Admin → Users if that's not wanted.");
        }

        $domain = CustomDomain::where('hostname', $host)->first();
        if ($domain && $domain->user_id !== $user->id) {
            $this->error("{$host} is already connected to another portfolio.");

            return self::FAILURE;
        }
        $domain ??= new CustomDomain(['hostname' => $host]);
        $domain->include_www = ! $this->option('no-www') && substr_count($host, '.') === 1;
        $domain->primary_www = $domain->include_www && $this->option('primary-www');
        $domain->ownership_trusted = (bool) $this->option('trusted') || $domain->ownership_trusted;
        $domain->user()->associate($user)->save();

        $verifier->verify($domain);
        $registry->forget();

        if ($domain->isVerified()) {
            $this->info("{$host} is live for @{$user->username}: ".implode(' and ', $domain->hostnames())." (main address {$domain->primaryHostname()}).");
            $this->line('GET /internal/custom-domains now lists it; the server sync adds the route and certificate within a minute.');

            return self::SUCCESS;
        }

        $this->warn("{$host} was added for @{$user->username} but isn't verified yet:");
        $this->line('  '.$domain->failure_reason);
        if (! $domain->ownership_trusted) {
            $this->line("  TXT {$domain->txtRecordName()} = {$domain->verification_token}");
        }
        $this->line('It is re-checked every 10 minutes, or run this command again after fixing DNS.');

        return self::FAILURE;
    }
}
