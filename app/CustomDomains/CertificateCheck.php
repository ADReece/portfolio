<?php

namespace App\CustomDomains;

use App\Models\CustomDomain;
use Illuminate\Support\Facades\Http;

/**
 * Whether a verified domain is served over HTTPS yet: the server's sync job
 * adds a route once DNS checks out, then Let's Encrypt issues a certificate
 * (HTTP-01), usually within a few minutes. We fetch a token from our own
 * /.well-known/portfolio-domain on the domain, with certificate checks on.
 */
class CertificateCheck
{
    public static function token(string $host): string
    {
        return substr(hash_hmac('sha256', strtolower($host), (string) config('app.key')), 0, 32);
    }

    /** Records secured_at once the primary hostname answers over valid HTTPS. */
    public function check(CustomDomain $domain): bool
    {
        if ($domain->status !== CustomDomain::VERIFIED) {
            return false;
        }
        if ($domain->secured_at) {
            return true;
        }

        $host = $domain->primaryHostname();
        try {
            $response = Http::timeout(8)->connectTimeout(5)->withoutRedirecting()->get("https://{$host}/.well-known/portfolio-domain");
            $secured = $response->successful() && trim($response->body()) === self::token($host);
        } catch (\Throwable) {
            $secured = false; // no certificate yet, or not routed yet
        }

        if ($secured) {
            $domain->forceFill(['secured_at' => now()])->save();
        }

        return $secured;
    }
}
