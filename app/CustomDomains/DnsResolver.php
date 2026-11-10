<?php

namespace App\CustomDomains;

/** DNS lookups, behind an interface so tests can fake them. */
interface DnsResolver
{
    /** @return list<string> IPv4 addresses (following CNAMEs) */
    public function a(string $host): array;

    /** @return list<string> */
    public function aaaa(string $host): array;

    /** @return list<string> CNAME targets, lower-case, no trailing dot */
    public function cname(string $host): array;

    /** @return list<string> */
    public function txt(string $host): array;
}
