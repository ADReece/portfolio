<?php

namespace App\Http\Controllers;

use App\CustomDomains\DomainRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /internal/custom-domains, for the server's Traefik sync job.
 * Lists every live hostname (verified, owner's plan includes custom domains).
 * Bearer CUSTOM_DOMAINS_SYNC_TOKEN; 404 when the token isn't configured or
 * the request comes in on a customer's domain.
 */
class InternalCustomDomainsController extends Controller
{
    public function __invoke(Request $request, DomainRegistry $registry): JsonResponse
    {
        $token = (string) config('custom_domains.sync_token');
        if ($token === '' || ! DomainRegistry::isPlatformHost($request->getHost())) {
            abort(404);
        }
        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $live = collect($registry->live())->sortKeys();

        return response()->json([
            'domains' => $live->keys()->values(),
            'items' => $live->map(fn ($e, $host) => [
                'domain' => $host,
                'username' => $e['username'],
                'primary' => $host === $e['primary'],
                'redirect_to' => $host === $e['primary'] ? null : $e['primary'],
            ])->values(),
        ], 200, ['Cache-Control' => 'no-store']);
    }
}
