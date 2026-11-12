<?php

namespace App\Http\Controllers;

use App\Billing\Payouts;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Photographer payouts: a Stripe Express account on Portfolio's own Connect
 * platform. Stripe hosts onboarding (identity, bank details); we only keep the
 * account id and whether it can take payments and receive payouts.
 */
class PayoutsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        // Coming back from Stripe, or details changed there: refresh the status.
        if (Payouts::available() && $user->stripe_connect_id && ! $user->is_demo && ! $user->stripe_connect_enabled) {
            $this->refreshStatus($user);
        }

        return view('billing.connect-stripe', [
            'comingSoon' => ! Payouts::available(),
            'started' => (bool) $user->stripe_connect_id,
        ]);
    }

    /** Create the Express account (first time) and send the photographer to Stripe's onboarding. */
    public function onboard(Request $request): RedirectResponse
    {
        abort_unless(Payouts::available(), 404);
        $user = $request->user();
        abort_if($user->is_demo, 403);

        if (! $user->hasFeature('selling')) {
            return redirect()->route('connect.stripe')->with('error', 'Selling isn\'t included in your plan.');
        }

        try {
            if (! $user->stripe_connect_id) {
                $account = Payouts::stripe()->accounts->create([
                    'type' => 'express',
                    'country' => config('services.portfolio_connect.country', 'GB'),
                    'email' => $user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                    'business_profile' => array_filter([
                        'url' => $user->username ? url('/'.$user->username) : null,
                        'product_description' => 'Photography prints and digital downloads sold through Portfolio',
                    ]),
                    'metadata' => ['app' => 'portfolio', 'user_id' => (string) $user->id],
                ], ['idempotency_key' => 'portfolio-connect-account-'.$user->id]);

                $user->forceFill(['stripe_connect_id' => $account->id, 'stripe_connect_enabled' => false])->save();
            }

            $link = Payouts::stripe()->accountLinks->create([
                'account' => $user->stripe_connect_id,
                'refresh_url' => route('connect.stripe.refresh'),
                'return_url' => route('connect.stripe.return'),
                'type' => 'account_onboarding',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe Express onboarding failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('connect.stripe')->with('error', 'We couldn\'t reach Stripe just now. Please try again.');
        }

        return redirect()->away($link->url);
    }

    /** Stripe sends the photographer back here when they finish (or leave) onboarding. */
    public function returned(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! Payouts::available() || ! $user->stripe_connect_id) {
            return redirect()->route('connect.stripe');
        }

        $this->refreshStatus($user);

        return $user->stripe_connect_enabled
            ? redirect()->route('connect.stripe')->with('success', 'Payouts are set up. You can now sell prints and downloads.')
            : redirect()->route('connect.stripe')->with('error', 'Stripe still needs a few details before you can be paid. Choose “Continue setup” to finish.');
    }

    /** An onboarding link expired or was reused: make a fresh one. */
    public function refresh(Request $request): RedirectResponse
    {
        if (! Payouts::available() || ! $request->user()->stripe_connect_id) {
            return redirect()->route('connect.stripe');
        }

        return $this->onboard($request);
    }

    /** The photographer's Stripe Express dashboard (payouts, bank details). */
    public function dashboard(Request $request): RedirectResponse
    {
        abort_unless(Payouts::available(), 404);
        $user = $request->user();
        abort_if($user->is_demo || ! $user->stripe_connect_id, 404);

        try {
            $link = Payouts::stripe()->accounts->createLoginLink($user->stripe_connect_id);
        } catch (\Throwable $e) {
            Log::warning('Stripe Express login link failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('connect.stripe')->with('error', 'We couldn\'t open your Stripe dashboard just now. Please try again.');
        }

        return redirect()->away($link->url);
    }

    /**
     * Stop selling. The Express account stays on Stripe (with any balance
     * still to pay out); connecting again creates a new one.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->is_demo, 403);

        $user->forceFill(['stripe_connect_id' => null, 'stripe_connect_enabled' => false])->save();

        return redirect()->route('connect.stripe')->with('success', 'Payouts are turned off. Clients can\'t buy prints or downloads until you set them up again.');
    }

    private function refreshStatus(User $user): void
    {
        try {
            Payouts::syncAccount($user, Payouts::stripe()->accounts->retrieve($user->stripe_connect_id)->toArray());
        } catch (\Throwable $e) {
            Log::warning('Stripe Express status check failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
