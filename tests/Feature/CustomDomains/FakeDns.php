<?php

namespace Tests\Feature\CustomDomains;

use App\CustomDomains\DnsResolver;

class FakeDns implements DnsResolver
{
    public array $a = [];
    public array $aaaa = [];
    public array $cname = [];
    public array $txt = [];

    public function a(string $host): array { return $this->a[$host] ?? []; }
    public function aaaa(string $host): array { return $this->aaaa[$host] ?? []; }
    public function cname(string $host): array { return $this->cname[$host] ?? []; }
    public function txt(string $host): array { return $this->txt[$host] ?? []; }
}
