<?php

namespace App\Http\Middleware;

use App\Tracking\Attribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Remembers first-landing UTMs and click ids (not on photographers' custom domains). */
class CaptureAttribution
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! app()->bound('custom-domain')) {
            Attribution::capture($request);
        }

        return $next($request);
    }
}
