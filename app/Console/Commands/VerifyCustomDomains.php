<?php

namespace App\Console\Commands;

use App\CustomDomains\DomainVerifier;
use App\Models\CustomDomain;
use Illuminate\Console\Command;

/**
 * Re-checks custom domains' DNS. Scheduled every 10 minutes for pending
 * domains, daily for verified ones and hourly for verified ones that are
 * failing (see App\Console\Kernel). A verified
 * domain whose DNS stops pointing here is taken offline after the grace period.
 */
class VerifyCustomDomains extends Command
{
    protected $signature = 'custom-domains:verify {--pending : Only pending domains} {--verified : Only verified domains} {--failing : Only verified domains whose last check failed}';

    protected $description = 'Check custom domains\' DNS and update their status';

    public function handle(DomainVerifier $verifier): int
    {
        $statuses = match (true) {
            $this->option('pending') => [CustomDomain::PENDING],
            $this->option('verified'), $this->option('failing') => [CustomDomain::VERIFIED],
            default => [CustomDomain::PENDING, CustomDomain::VERIFIED],
        };

        $checked = 0;
        CustomDomain::whereIn('status', $statuses)
            ->when($this->option('failing'), fn ($q) => $q->where('status', CustomDomain::VERIFIED)->whereNotNull('failing_since'))->orderBy('id')->each(function (CustomDomain $domain) use ($verifier, &$checked) {
            $before = $domain->status;
            $verifier->verify($domain);
            $checked++;
            if ($before !== $domain->status) {
                $this->line("{$domain->hostname}: {$before} -> {$domain->status}".($domain->failure_reason ? " ({$domain->failure_reason})" : ''));
            }
        });

        $this->info("Checked {$checked} domain(s).");

        return self::SUCCESS;
    }
}
