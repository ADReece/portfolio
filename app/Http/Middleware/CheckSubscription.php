<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $feature = null)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admins and users with a feature override can use everything,
        // matching User::hasFeature().
        if ($user->is_admin || $user->isFeatureOverrideActive()) {
            return $next($request);
        }

        if (!$user->subscriptionPlan) {
            return redirect()->route('pricing')
                ->with('error', 'Please select a subscription plan to continue.');
        }

        if ($feature && !$user->hasFeature($feature)) {
            return redirect()->back()
                ->with('error', 'This feature is not available on your current plan. Please upgrade to access it.');
        }

        return $next($request);
    }
}
