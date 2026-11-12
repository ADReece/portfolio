<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrintPurchaseController extends Controller
{
    public function show(Request $request, Photo $photo): View
    {
        $photographer = $photo->user;

        abort_if(!$this->isPhotoPurchasable($request, $photo), 404);

        $enabledProducts = $this->sellableProducts($photographer);
        abort_if($enabledProducts->isEmpty(), 404);

        // Remember where the client came from so we can send them back there.
        $previous = url()->previous();
        if ($previous && $previous !== $request->fullUrl() && str_starts_with($previous, url('/'))) {
            $request->session()->put('print_purchase_return_url', $previous);
        }

        return view('photos.buy-print', [
            'returnUrl' => $request->session()->get('print_purchase_return_url'),
            'countries' => \App\Support\Countries::LIST,
            'defaultCountry' => \App\Support\Countries::defaultCode(),
            'photo' => $photo,
            'photographer' => $photographer,
            'enabledProducts' => $enabledProducts,
        ]);
    }

    public function store(Request $request, Photo $photo): RedirectResponse
    {
        $photographer = $photo->user;

        abort_if(!$this->isPhotoPurchasable($request, $photo), 404);

        if ($photographer->is_demo) {
            return back()->withInput()->with('demo', 'This is a demo portfolio, so prints can\'t be ordered here. On your own portfolio, the client pays by card and the print is made and posted to them.');
        }

        $validated = $request->validate([
            'prodigi_product_id' => ['required', 'integer', 'exists:prodigi_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            // The DNS check needs the network, so tests check the format only.
            'customer_email' => ['required', app()->runningUnitTests() ? 'email:rfc' : 'email:rfc,dns', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'shipping_address_line1' => ['required', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_county' => ['required', 'string', 'max:255'],
            'shipping_postcode' => ['required', 'string', 'max:32'],
            'shipping_country_code' => ['required', 'string', 'size:2', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Countries::LIST))],
        ]);

        $prodigiProduct = $this->sellableProducts($photographer)->firstWhere('id', (int) $validated['prodigi_product_id']);
        if (! $prodigiProduct) {
            return back()->withErrors(['prodigi_product_id' => 'This print product is not available for this photographer.'])->withInput();
        }

        // The options the client chose (frame colour, paper...), from those offered.
        $attributes = [];
        foreach ($prodigiProduct->offeredAttributes() as $name => $values) {
            $chosen = count($values) === 1 ? $values[0] : (string) $request->input("options.$name", '');
            if (! in_array($chosen, $values, true)) {
                return back()->withErrors(["options.$name" => 'Choose a '.strtolower(ProdigiProduct::labelAttribute($name)).'.'])->withInput();
            }
            $attributes[$name] = $chosen;
        }

        $unitPrice = (float) $prodigiProduct->unit_price;
        $quantity = (int) $validated['quantity'];
        $total = round($unitPrice * $quantity, 2);
        $country = strtoupper($validated['shipping_country_code']);

        // The client pays the price (print and delivery included). Prodigi's
        // cost for these prints delivered to this address comes off it, then
        // the platform fee; the photographer receives the rest.
        $pricing = app(\App\Prints\PrintPricing::class);
        try {
            $quote = app(\App\Service\ProdigiService::class)->quote(
                $prodigiProduct->sku, $attributes, $country, \App\Support\Currency::code(), config('prints.shipping_method'), $quantity
            );
            $printCost = round((float) data_get($quote, 'costSummary.items.amount') + (float) data_get($quote, 'costSummary.shipping.amount', 0), 2);
            if (! is_numeric(data_get($quote, 'costSummary.items.amount'))) {
                throw new \RuntimeException('Quote had no item cost');
            }
        } catch (\Throwable $e) {
            \Log::warning('Print checkout quote failed', ['sku' => $prodigiProduct->sku, 'country' => $country, 'error' => $e->getMessage()]);

            return back()->withErrors(['shipping_country_code' => 'We couldn\'t work out delivery for this order just now. Please try again in a moment.'])->withInput();
        }
        $platformFee = $pricing->platformFee($total);
        $photographerAmount = $pricing->payout($total, $printCost);
        if ($photographerAmount < 0) {
            return back()->withErrors(['shipping_country_code' => 'Sorry, this print can\'t be delivered to '.(\App\Support\Countries::LIST[$country] ?? $country).' at this price.'])->withInput();
        }

        $order = DB::transaction(function () use ($photographer, $photo, $prodigiProduct, $validated, $quantity, $unitPrice, $total, $platformFee, $photographerAmount, $attributes, $printCost) {
            $product = Product::query()->firstOrCreate(
                [
                    'user_id' => $photographer->id,
                    'photo_id' => $photo->id,
                    'prodigi_sku' => $prodigiProduct->sku,
                ],
                [
                    'name' => $prodigiProduct->name,
                    'description' => $prodigiProduct->description,
                    'type' => 'print_poster',
                    'price' => $unitPrice,
                    'active' => true,
                ]
            );

            if ((float) $product->price !== $unitPrice) {
                $product->update(['price' => $unitPrice]);
            }

            $order = Order::query()->create([
                'user_id' => $photographer->id,
                'customer_email' => $validated['customer_email'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'shipping_address_line1' => $validated['shipping_address_line1'],
                'shipping_address_line2' => $validated['shipping_address_line2'] ?? null,
                'shipping_city' => $validated['shipping_city'],
                'shipping_county' => $validated['shipping_county'],
                'shipping_postcode' => $validated['shipping_postcode'],
                'shipping_country_code' => strtoupper($validated['shipping_country_code']),
                'total' => $total,
                'platform_fee' => $platformFee,
                'print_cost' => $printCost,
                'photographer_amount' => $photographerAmount,
                'status' => 'pending',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'prodigi_attributes' => $attributes ?: null,
            ]);

            return $order;
        });

        // A destination charge on Portfolio's Connect platform: the client pays
        // Portfolio, Stripe moves the photographer's share to their Express
        // account, and the application fee (print cost + our fee) stays with us.
        $session = \App\Billing\Payouts::stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => route('print-purchase.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('print-purchase.cancel', ['order' => $order->id]),
            'payment_method_types' => ['card'],
            'customer_email' => $validated['customer_email'],
            'metadata' => ['app' => 'portfolio', 'order_id' => (string) $order->id],
            'payment_intent_data' => [
                'application_fee_amount' => \App\Billing\Payouts::applicationFee($total, $photographerAmount),
                'transfer_data' => ['destination' => $photographer->stripe_connect_id],
                'description' => 'Print order '.$order->id,
                'metadata' => [
                    'app' => 'portfolio',
                    'order_id' => (string) $order->id,
                    'photo_id' => (string) $photo->id,
                    'photographer_id' => (string) $photographer->id,
                ],
            ],
            'line_items' => [
                [
                    'quantity' => $quantity,
                    'price_data' => [
                        'currency' => \App\Support\Currency::stripeCode(),
                        'unit_amount' => (int) round($unitPrice * 100),
                        'product_data' => [
                            'name' => $prodigiProduct->name,
                            'description' => 'Print of photo by @' . $photographer->username,
                            'metadata' => [
                                'prodigi_sku' => $prodigiProduct->sku,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request, Order $order): View
    {
        return view('photos.purchase-success', $this->resultViewData($request, $order));
    }

    public function cancel(Request $request, Order $order): View
    {
        return view('photos.purchase-cancel', $this->resultViewData($request, $order));
    }

    private function resultViewData(Request $request, Order $order): array
    {
        $order->load(['user', 'items.product.photo']);
        $item = $order->items->first();

        return [
            'order' => $order,
            'photographer' => $order->user,
            'item' => $item,
            'photo' => $item?->product?->photo,
            'returnUrl' => $request->session()->get('print_purchase_return_url'),
        ];
    }

    /**
     * A photo can be bought when its photographer sells prints and the client
     * can see it: either it's on the public portfolio, or it's in a private
     * gallery the client has unlocked (password or share link).
     */
    /**
     * The photographer's enabled, active products that are sellable at their
     * price (never below the latest cost), each with its unit_price.
     */
    private function sellableProducts(\App\Models\User $photographer): \Illuminate\Support\Collection
    {
        $pricing = app(\App\Prints\PrintPricing::class);

        return $photographer->enabledProdigiProducts()
            ->select('prodigi_products.*')
            ->catalogueOrder()
            ->get()
            ->each(fn (ProdigiProduct $p) => $p->unit_price = $pricing->priceFor($p, $p->pivot->retail_price))
            ->filter(fn (ProdigiProduct $p) => $pricing->isSellableAt($p, $p->unit_price))
            ->values();
    }

    private function isPhotoPurchasable(Request $request, Photo $photo): bool
    {
        $photographer = $photo->user;
        $collection = $photo->set?->collection;

        if (! \App\Billing\Payouts::canPay($photographer)
            || ! $photo->isReady()
            || ! $photographer || ! $collection
            || ! $photographer->hasFeature('selling')
            || ! $photographer->enabledProdigiProducts()->exists()) {
            return false;
        }

        return \App\Support\GalleryAccess::canViewPhoto($request, $photo);
    }
}
