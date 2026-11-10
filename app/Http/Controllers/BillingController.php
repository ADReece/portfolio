<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment; // add for SCA handling

class BillingController extends Controller
{
    public function pricing(Request $request)
    {
        $interval = $request->query('interval', 'month');
        $plans = SubscriptionPlan::where('active', true)->orderBy('sort_order')->get();
        return view('billing.pricing', compact('plans', 'interval'));
    }

    public function checkout(Request $request, SubscriptionPlan $plan)
    {
        $interval = $request->query('interval', 'month');
        if ($plan->isFree()) {
            Auth::user()->update(['subscription_plan_id' => $plan->id]);
            return redirect()->route('dashboard')->with('success', "You're now on the Free plan.");
        }
        return view('billing.checkout', compact('plan', 'interval'));
    }

    public function processCheckout(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'interval' => 'nullable|in:month,year'
        ]);
        $interval = $request->input('interval', 'month');

        $user = $request->user();

        try {
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            $priceId = $plan->stripePriceFor($interval);
            if (!$priceId) {
                return back()->with('error', 'This plan is not configured for '.($interval === 'year' ? 'yearly' : 'monthly').' billing. Please contact support.');
            }

            // New monthly subscriptions get the launch offer coupon while it runs.
            app(\App\Billing\LaunchOffer::class)
                ->apply($user->newSubscription('default', $priceId), $interval)
                ->create($request->payment_method);

            $user->update(['subscription_plan_id' => $plan->id]);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');
        } catch (IncompletePayment $exception) {
            // Redirect to SCA confirmation page
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('dashboard')]
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Payment failed: '.$e->getMessage());
        }
    }

    public function billingPortal(Request $request)
    {
        try {
            return $request->user()->redirectToBillingPortal(route('dashboard'));
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            \Log::error('Billing portal error: ' . $e->getMessage());

            // Check if it's the portal configuration error
            if (str_contains($e->getMessage(), 'configuration') || str_contains($e->getMessage(), 'portal')) {
                return redirect()->route('dashboard')->with('error',
                    'The billing portal is not yet configured. You can still manage your subscription using the Cancel/Resume buttons below, or contact support for assistance.'
                );
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Unexpected billing portal error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error',
                'Unable to access billing portal at this time. Please try again later or contact support.'
            );
        }
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancel();

            return redirect()->back()
                ->with('success', 'Your subscription will be cancelled at the end of the billing period.');
        }

        return redirect()->back()
            ->with('error', 'No active subscription found.');
    }

    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            return redirect()->back()
                ->with('success', 'Your subscription has been resumed!');
        }

        return redirect()->back()
            ->with('error', 'No resumable subscription found.');
    }

    public function swap(Request $request, SubscriptionPlan $plan)
    {
        $interval = $request->input('interval', 'month');
        $user = $request->user();
        if (!$user->subscribed('default')) {
            return redirect()->route('checkout', ['plan' => $plan->id, 'interval' => $interval]);
        }
        try {
            $priceId = $plan->stripePriceFor($interval);
            if (!$priceId) {
                return back()->with('error', 'This plan is not configured for '.($interval === 'year' ? 'yearly' : 'monthly').' billing.');
            }
            $user->subscription('default')->swap($priceId);
            $user->update(['subscription_plan_id' => $plan->id]);
            return redirect()->route('dashboard')->with('success', 'Plan updated successfully!');
        } catch (IncompletePayment $exception) {
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('dashboard')]
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update plan: '.$e->getMessage());
        }
    }

    // Stripe Connect methods for receiving payments
    public function connectStripe(Request $request)
    {
        if (! \App\Billing\Payouts::available()) {
            return view('billing.connect-stripe', ['connectUrl' => null, 'comingSoon' => true]);
        }

        // The OAuth "state" ties Stripe's redirect back to this browser session,
        // so nobody can trick a photographer into linking someone else's account.
        $state = \Illuminate\Support\Str::random(40);
        $request->session()->put('stripe_connect_state', $state);

        $connectUrl = 'https://connect.stripe.com/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.stripe.connect_client_id'),
            'scope' => 'read_write',
            'redirect_uri' => route('connect.stripe.callback'),
            'state' => $state,
        ]);

        return view('billing.connect-stripe', ['connectUrl' => $connectUrl]);
    }

    public function handleConnectCallback(Request $request)
    {
        if (! \App\Billing\Payouts::available()) {
            return redirect()->route('connect.stripe');
        }

        // Handle OAuth callback from Stripe Connect
        $code = $request->query('code');
        $expectedState = $request->session()->pull('stripe_connect_state');

        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('connect.stripe')
                ->with('error', 'That Stripe connection link has expired. Please try connecting again.');
        }

        if (!$code) {
            return redirect()->route('connect.stripe')
                ->with('error', $request->query('error_description') ?: "Stripe wasn't connected. Please try again.");
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));

            $response = $stripe->oauth->token([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            $user = Auth::user();
            $user->update([
                'stripe_connect_id' => $response->stripe_user_id,
                'stripe_connect_enabled' => true,
            ]);

            return redirect()->route('connect.stripe')
                ->with('success', 'Stripe is connected. Print earnings will be paid to your account.');
        } catch (\Exception $e) {
            \Log::warning('Stripe Connect callback failed: '.$e->getMessage());
            return redirect()->route('connect.stripe')
                ->with('error', "We couldn't finish connecting Stripe. Please try again.");
        }
    }

    public function disconnectStripe(Request $request)
    {
        $user = Auth::user();

        try {
            if ($user->stripe_connect_id) {
                // Optionally revoke the connection on Stripe's side
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripe->oauth->deauthorize([
                    'stripe_user_id' => $user->stripe_connect_id,
                ]);
            }

            $user->update([
                'stripe_connect_id' => null,
                'stripe_connect_enabled' => false,
            ]);

            return redirect()->back()
                ->with('success', 'Stripe account disconnected.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }

    public function downloadInvoice(Request $request, string $invoiceId)
    {
        // Customize invoice vendor/product labels if desired
        $data = [
            'vendor' => config('app.name'),
            'product' => 'Subscription',
        ];
        return $request->user()->downloadInvoice($invoiceId, $data);
    }
}
