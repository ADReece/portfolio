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

        $enabledProducts = $photographer->enabledProdigiProducts()
            ->select('prodigi_products.*')
            ->orderBy('prodigi_products.category')
            ->orderBy('prodigi_products.name')
            ->get();

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

        $validated = $request->validate([
            'prodigi_product_id' => ['required', 'integer', 'exists:prodigi_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'customer_email' => ['required', 'email:rfc,dns', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'shipping_address_line1' => ['required', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_county' => ['required', 'string', 'max:255'],
            'shipping_postcode' => ['required', 'string', 'max:32'],
            'shipping_country_code' => ['required', 'string', 'size:2', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Countries::LIST))],
        ]);

        $prodigiProduct = ProdigiProduct::query()->findOrFail((int) $validated['prodigi_product_id']);
        $pivot = $photographer->prodigiProducts()
            ->where('prodigi_products.id', $prodigiProduct->id)
            ->first();

        if (!$pivot || !$pivot->pivot->is_enabled) {
            return back()->withErrors(['prodigi_product_id' => 'This print product is not available for this photographer.'])->withInput();
        }

        $unitPrice = (float) ($pivot->pivot->retail_price ?? 29.99);
        $quantity = (int) $validated['quantity'];
        $total = $unitPrice * $quantity;
        $platformFeeRate = config('services.platform_fee_percent') / 100;
        $platformFee = round($total * $platformFeeRate, 2);
        $photographerAmount = round($total - $platformFee, 2);

        $order = DB::transaction(function () use ($photographer, $photo, $prodigiProduct, $validated, $quantity, $unitPrice, $total, $platformFee, $photographerAmount) {
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
                'photographer_amount' => $photographerAmount,
                'status' => 'pending',
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice,
            ]);

            return $order;
        });

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => route('print-purchase.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('print-purchase.cancel', ['order' => $order->id]),
            'payment_method_types' => ['card'],
            'customer_email' => $validated['customer_email'],
            'payment_intent_data' => [
                'metadata' => [
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
    private function isPhotoPurchasable(Request $request, Photo $photo): bool
    {
        $photographer = $photo->user;
        $collection = $photo->set?->collection;

        if (! $photographer || ! $collection
            || ! $photographer->hasFeature('selling')
            || ! $photographer->enabledProdigiProducts()->exists()) {
            return false;
        }

        if ($collection->private) {
            return ProfileController::collectionIsUnlocked($request, $collection);
        }

        return ! $photo->private
            && ! $photo->hide_from_portfolio
            && ! $photo->set->hide_from_portfolio
            && ! $collection->hide_from_portfolio;
    }
}
