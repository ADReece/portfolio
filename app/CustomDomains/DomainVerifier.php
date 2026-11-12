<?php

namespace App\CustomDomains;

use App\Models\CustomDomain;
use Carbon\CarbonImmutable;

/**
 * Checks a domain's DNS: the TXT record proves ownership, and every hostname
 * it serves must point here (A record or CNAME), with no stray AAAA record.
 */
class DomainVerifier
{
    public function __construct(private DnsResolver $dns) {}

    /** @return list<string> problems, empty when everything is right */
    public function problems(CustomDomain $domain): array
    {
        $problems = [];

        $txt = $domain->ownership_trusted ? [] : $this->dns->txt($domain->txtRecordName());
        if (! $domain->ownership_trusted && ! in_array($domain->verification_token, $txt, true)) {
            $problems[] = "The TXT record {$domain->txtRecordName()} doesn't contain {$domain->verification_token} yet.";
        }

        foreach ($domain->hostnames() as $host) {
            if ($problem = $this->pointingProblem($host)) {
                $problems[] = $problem;
            }
        }

        return $problems;
    }

    private function pointingProblem(string $host): ?string
    {
        $targetIp = config('custom_domains.target_ip');
        $cnames = $this->dns->cname($host);
        $viaCname = (bool) array_intersect($cnames, config('custom_domains.cname_targets'));
        $ips = $this->dns->a($host);

        if (! $viaCname && $ips && collect($ips)->every(fn ($ip) => self::isCloudflare($ip))) {
            return "{$host} goes through Cloudflare's proxy (orange cloud). In Cloudflare's DNS settings, set the record to \"DNS only\" (grey cloud) so we can secure it.";
        }
        if (! $viaCname && ! in_array($targetIp, $ips, true)) {
            return $ips || $cnames
                ? "{$host} points somewhere else (".implode(', ', $cnames ?: $ips)."). Point it at {$targetIp}."
                : "{$host} doesn't point anywhere yet. Add an A record for {$targetIp}.";
        }
        if (count(array_diff($ips, [$targetIp])) > 0 && ! $viaCname) {
            return "{$host} also points at ".implode(', ', array_diff($ips, [$targetIp])).". Remove the other A records.";
        }

        $ipv6 = config('custom_domains.target_ipv6');
        $aaaa = $this->dns->aaaa($host);
        if ($aaaa && (! $ipv6 || array_diff($aaaa, [$ipv6]))) {
            return "{$host} has an AAAA (IPv6) record pointing elsewhere. Remove it.";
        }

        return null;
    }

    public static function isCloudflare(string $ip): bool
    {
        foreach (config('custom_domains.cloudflare_ranges', []) as $range) {
            [$subnet, $bits] = explode('/', $range);
            $mask = -1 << (32 - (int) $bits);
            if ((ip2long($ip) & $mask) === (ip2long($subnet) & $mask)) {
                return true;
            }
        }

        return false;
    }

    /** Runs a check and records the outcome on the domain. */
    public function verify(CustomDomain $domain, ?CarbonImmutable $now = null): CustomDomain
    {
        $now ??= CarbonImmutable::now();
        $problems = $this->problems($domain);
        $domain->last_checked_at = $now;

        if (! $problems) {
            $domain->status = CustomDomain::VERIFIED;
            $domain->verified_at ??= $now;
            $domain->failure_reason = null;
            $domain->failing_since = null;
        } else {
            $domain->failure_reason = implode(' ', $problems);
            if ($domain->status === CustomDomain::VERIFIED) {
                // Was working: allow time for a DNS change, then take it offline.
                $domain->failing_since ??= $now;
                if ($domain->failing_since->addHours(config('custom_domains.grace_hours'))->lte($now)) {
                    $domain->status = CustomDomain::FAILED;
                    $domain->secured_at = null; // needs securing again if it comes back
                }
            } elseif ($domain->status === CustomDomain::PENDING
                && $domain->created_at && $domain->created_at->addDays(config('custom_domains.pending_days'))->lte($now)) {
                $domain->status = CustomDomain::FAILED;
            }
        }

        $domain->save();

        return $domain;
    }
}
