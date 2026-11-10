<?php

namespace App\Http\Controllers;

use App\CustomDomains\DomainVerifier;
use App\CustomDomains\Hostname;
use App\Models\CustomDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Settings → Your domain: add, verify, configure and remove custom domains. */
class CustomDomainController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('profile.custom-domains', [
            'user' => $user,
            'domains' => $user->customDomains()->latest()->get(),
            'limit' => $user->customDomainLimit(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['hostname' => ['required', 'string', 'max:253']]);

        if ($user->customDomains()->count() >= $user->customDomainLimit()) {
            throw ValidationException::withMessages(['hostname' => $user->customDomainLimit() === 0
                ? 'Custom domains are included on paid plans.'
                : 'Your plan includes '.$user->customDomainLimit().' custom domain. Remove the current one to add another.']);
        }
        if (! $user->username) {
            throw ValidationException::withMessages(['hostname' => 'Choose a username for your portfolio first.']);
        }

        $parsed = Hostname::normalise($request->input('hostname'));
        if (! $parsed) {
            throw ValidationException::withMessages(['hostname' => 'Enter a domain you own, like yourname.com.']);
        }
        $host = $parsed['hostname'];
        $taken = CustomDomain::where('hostname', $host)
            ->orWhere(fn ($q) => $q->where('include_www', true)->where('hostname', preg_replace('/^www\./', '', $host)))
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages(['hostname' => 'That domain is already connected to a portfolio.']);
        }

        $domain = new CustomDomain([
            'hostname' => $host,
            // www makes sense for a bare domain (yourname.com), not for photos.yourname.com.
            'include_www' => $parsed['had_www'] || substr_count($host, '.') === 1,
            'primary_www' => $parsed['had_www'],
        ]);
        $domain->user()->associate($user)->save();

        return redirect()->route('custom-domains.index')->with('success', 'Domain added. Now add the DNS records below.');
    }

    public function update(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorizeOwner($request, $domain);
        $data = $request->validate(['include_www' => ['boolean'], 'primary_www' => ['boolean']]);
        $domain->include_www = (bool) ($data['include_www'] ?? false);
        $domain->primary_www = $domain->include_www && (bool) ($data['primary_www'] ?? false);
        if ($domain->isDirty('include_www') && $domain->include_www && $domain->isVerified()) {
            // The new www hostname has to be checked before it's served.
            $domain->status = CustomDomain::PENDING;
        }
        $domain->save();

        return back()->with('success', 'Domain settings saved.');
    }

    public function verify(Request $request, CustomDomain $domain, DomainVerifier $verifier): RedirectResponse
    {
        $this->authorizeOwner($request, $domain);
        if (RateLimiter::tooManyAttempts('verify-domain:'.$domain->id, 6)) {
            return back()->with('error', 'Please wait a minute before checking again.');
        }
        RateLimiter::hit('verify-domain:'.$domain->id, 60);

        $verifier->verify($domain);

        return $domain->isVerified()
            ? back()->with('success', 'Verified. Your portfolio will be live on '.$domain->displayHostname($domain->primaryHostname()).' within a few minutes, once its security certificate is issued.')
            : back()->with('error', 'Not verified yet. DNS changes can take up to a few hours to spread.');
    }

    public function destroy(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorizeOwner($request, $domain);
        $domain->delete();

        return redirect()->route('custom-domains.index')->with('success', 'Domain removed. Your portfolio is back on '.parse_url(config('app.url'), PHP_URL_HOST).'.');
    }

    private function authorizeOwner(Request $request, CustomDomain $domain): void
    {
        abort_unless($domain->user_id === $request->user()->id, 404);
    }
}
