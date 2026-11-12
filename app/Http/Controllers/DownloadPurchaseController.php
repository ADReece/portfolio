<?php

namespace App\Http\Controllers;

use App\Downloads\DigitalPricing;
use App\Downloads\DigitalSales;
use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Currency;
use App\Support\GalleryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\StripeClient;

/**
 * Clients buying full-resolution downloads from a collection: pick photos,
 * pay with Stripe Checkout, and get an emailed link to the originals
 * (App\Jobs\DeliverDigitalOrder, after the payment webhook).
 */
class DownloadPurchaseController extends Controller
{
    public function show(Request $request, Collection $collection, DigitalPricing $pricing): View|RedirectResponse
    {
        if ($redirect = $this->guard($request, $collection)) {
            return $redirect;
        }

        $photos = DigitalSales::photosFor($request, $collection);
        abort_if($photos->isEmpty(), 404);
        $offer = $pricing->offerFor($collection);
        $preselected = collect((array) $request->query('photo', []))->map(fn ($id) => (string) $id)
            ->intersect($photos->pluck('id')->map(fn ($id) => (string) $id))->values()->all();

        return view('downloads.buy', [
            'collection' => $collection,
            'photographer' => $collection->user,
            'photos' => $photos,
            'offer' => $offer,
            'quotes' => $pricing->quoteTable($offer, $photos->count()),
            'preselected' => $preselected,
            'galleryUrl' => route('profile.collection', ['username' => $collection->user->username, 'collection_id' => $collection->id]),
        ]);
    }

    public function store(Request $request, Collection $collection, DigitalPricing $pricing): RedirectResponse
    {
        if ($redirect = $this->guard($request, $collection)) {
            return $redirect;
        }

        if ($collection->user->is_demo) {
            return back()->withInput()->with('demo', 'This is a demo portfolio, so nothing is charged. On your own portfolio, the client pays by card and gets their photos by email straight away.');
        }

        $validated = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['string'],
            // The DNS check needs the network, so tests check the format only.
            'customer_email' => ['required', app()->runningUnitTests() ? 'email:rfc' : 'email:rfc,dns', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
        ], ['photo_ids.required' => 'Choose at least one photo.']);

        $available = DigitalSales::photosFor($request, $collection);
        $chosen = $available->whereIn('id', array_map('strval', $validated['photo_ids']))->values();
        if ($chosen->isEmpty()) {
            throw ValidationException::withMessages(['photo_ids' => 'Choose at least one photo.']);
        }

        $quote = $pricing->quote($pricing->offerFor($collection), $chosen->count(), $available->count());
        if (! $quote) {
            throw ValidationException::withMessages(['photo_ids' => 'These photos can\'t be bought on their own. Choose more photos, or the whole collection.']);
        }
        // When the whole collection is the best price, the client gets every photo.
        $photos = $quote['all'] ? $available : $chosen;
        $total = $quote['total'];
        $photographer = $collection->user;

        $order = DB::transaction(function () use ($photographer, $collection, $photos, $validated, $quote, $total, $pricing) {
            $order = Order::create([
                'kind' => Order::DIGITAL,
                'user_id' => $photographer->id,
                'collection_id' => $collection->id,
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'] ?? null,
                'total' => $total,
                'platform_fee' => $pricing->platformFee($total),
                'photographer_amount' => $pricing->payout($total),
                'pricing_breakdown' => $quote['lines'],
                'status' => 'pending',
            ]);

            // Spread the total over the photos (whole pence, remainder on the first).
            $pence = (int) round($total * 100);
            $each = intdiv($pence, $photos->count());
            foreach ($photos->values() as $i => $photo) {
                $product = Product::firstOrCreate(
                    ['user_id' => $photographer->id, 'photo_id' => $photo->id, 'type' => 'digital'],
                    ['name' => 'Full-resolution download', 'price' => $each / 100, 'active' => true],
                );
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => ($each + ($i === 0 ? $pence - $each * $photos->count() : 0)) / 100,
                ]);
            }

            return $order;
        });

        $count = $photos->count();
        $session = app(StripeClient::class)->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => route('download-purchase.success', ['order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('download-purchase.cancel', ['order' => $order->id]),
            'payment_method_types' => ['card'],
            'customer_email' => $validated['customer_email'],
            // The Stripe account is shared with other Ascend Digital apps.
            'metadata' => ['app' => 'portfolio', 'order_id' => (string) $order->id],
            'payment_intent_data' => [
                'metadata' => [
                    'app' => 'portfolio',
                    'order_id' => (string) $order->id,
                    'collection_id' => (string) $collection->id,
                    'photographer_id' => (string) $photographer->id,
                ],
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => Currency::stripeCode(),
                    'unit_amount' => (int) round($total * 100),
                    'product_data' => [
                        'name' => $count.' full-resolution '.Str::plural('photo', $count).' from '.$collection->name,
                        'description' => 'Digital download from @'.$photographer->username.'. You\'ll get an email with your download link.',
                    ],
                ],
            ]],
        ]);
        $order->update(['stripe_checkout_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    /** Back from Stripe. Once the payment webhook has arrived, the download link is shown here too. */
    public function success(Request $request, Order $order): View
    {
        abort_unless($order->isDigital(), 404);
        // Only the buyer coming back from Stripe sees the link here; it's also emailed.
        $isBuyer = $order->stripe_checkout_session_id && hash_equals($order->stripe_checkout_session_id, (string) $request->query('session_id'));

        return view('downloads.purchase-result', [
            'order' => $order->load('user', 'collection'),
            'paid' => $order->paid_at !== null,
            'downloadUrl' => $isBuyer && $order->paid_at ? $order->downloadUrl() : null,
            'cancelled' => false,
        ]);
    }

    public function cancel(Order $order): View
    {
        abort_unless($order->isDigital(), 404);

        return view('downloads.purchase-result', [
            'order' => $order->load('user', 'collection'),
            'paid' => false,
            'downloadUrl' => null,
            'cancelled' => true,
        ]);
    }

    /** Not on sale or not visible: back to the gallery (which asks for the password if needed). */
    private function guard(Request $request, Collection $collection): ?RedirectResponse
    {
        abort_unless(DigitalSales::onSale($collection), 404);

        if ($collection->private && ! GalleryAccess::collectionIsUnlocked($request, $collection)) {
            return redirect()->route('profile.collection', ['username' => $collection->user->username, 'collection_id' => $collection->id]);
        }
        abort_if(! $collection->private && $collection->status !== 'Published', 404);

        return null;
    }
}
