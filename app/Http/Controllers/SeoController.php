<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml for the host being served: the main site, or a
 * photographer's own domain (its portfolio pages only).
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow:\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $domain = app()->bound('custom-domain') ? app('custom-domain') : null;

        if ($domain) {
            $user = User::where('username', $domain['username'])->firstOrFail();
            $urls = collect([
                route('profile.view', $user->username),
                route('profile.collections', $user->username),
            ])->merge($user->collections()
                ->where('status', 'Published')->where('hide_from_portfolio', false)->where('private', false)
                ->pluck('id')
                ->map(fn ($id) => route('profile.collection', ['username' => $user->username, 'collection_id' => $id])));
        } else {
            $urls = collect([url('/'), route('pricing'), route('about'), route('terms'), route('privacy')])
                ->merge(collect(array_keys(config('marketing.showcases', [])))->map(fn ($slug) => route('examples.show', $slug)));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls->unique()->map(fn ($u) => '  <url><loc>'.e($u).'</loc></url>')->implode("\n")
            ."\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
