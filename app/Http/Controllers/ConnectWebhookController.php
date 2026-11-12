<?php

namespace App\Http\Controllers;

use App\Billing\Payouts;
use App\Billing\SaleEvents;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhooks from Portfolio's own Stripe Connect platform (/stripe/connect/webhook).
 * Signed with PORTFOLIO_CONNECT_STRIPE_WEBHOOK_SECRET. Payment events come from
 * the platform account (destination charges); account.updated comes from the
 * photographers' Express accounts ("Connected accounts" events).
 */
class ConnectWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! Payouts::available()) {
            return response('Payouts are not set up', 404);
        }

        // Stripe signs "Your account" and "Connected accounts" endpoints with
        // different secrets; both can be set, separated by a comma.
        $secrets = array_filter(array_map('trim', explode(',', (string) config('services.portfolio_connect.webhook_secret'))));
        $event = null;
        foreach ($secrets as $secret) {
            try {
                $event = Webhook::constructEvent(
                    $request->getContent(),
                    (string) $request->header('Stripe-Signature'),
                    $secret,
                    (int) config('cashier.webhook.tolerance', 300),
                )->toArray();
                break;
            } catch (SignatureVerificationException $e) {
                continue;
            } catch (\UnexpectedValueException $e) {
                return response('Invalid payload', 400);
            }
        }
        if ($event === null) {
            return response('Invalid signature', 403);
        }

        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? '') {
            'payment_intent.succeeded' => SaleEvents::paid($object),
            'payment_intent.payment_failed' => SaleEvents::failed($object),
            'charge.refunded' => SaleEvents::refunded($object),
            'account.updated' => $this->accountUpdated($object),
            'account.application.deauthorized' => $this->deauthorized((string) ($event['account'] ?? '')),
            default => null,
        };

        return response('Webhook handled', 200);
    }

    private function accountUpdated(array $account): void
    {
        if ($user = User::where('stripe_connect_id', $account['id'] ?? '')->first()) {
            Payouts::syncAccount($user, $account);
        }
    }

    /** The photographer removed Portfolio from their Stripe account. */
    private function deauthorized(string $accountId): void
    {
        if ($accountId === '') {
            return;
        }

        User::where('stripe_connect_id', $accountId)->where('is_demo', false)
            ->update(['stripe_connect_id' => null, 'stripe_connect_enabled' => false]);
        Log::info('Stripe Connect account deauthorized', ['account' => $accountId]);
    }
}
