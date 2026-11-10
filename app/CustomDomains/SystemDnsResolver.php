<?php

namespace App\CustomDomains;

/**
 * Live lookups with PHP's resolver. Results aren't cached by the app; each
 * check asks DNS again.
 */
class SystemDnsResolver implements DnsResolver
{
    public function a(string $host): array
    {
        return $this->lookup($host, DNS_A, 'ip');
    }

    public function aaaa(string $host): array
    {
        return $this->lookup($host, DNS_AAAA, 'ipv6');
    }

    public function cname(string $host): array
    {
        return array_map(fn ($t) => rtrim(strtolower($t), '.'), $this->lookup($host, DNS_CNAME, 'target'));
    }

    public function txt(string $host): array
    {
        return array_map('trim', $this->lookup($host, DNS_TXT, 'txt'));
    }

    private function lookup(string $host, int $type, string $field): array
    {
        $records = @dns_get_record($host.'.', $type);

        return collect($records ?: [])->pluck($field)->filter()->values()->all();
    }
}
