<?php

namespace App\Service;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

class ProdigiOrderService
{
    private ProdigiService $prodigiService;

    public function __construct(ProdigiService $prodigiService)
    {
        $this->prodigiService = $prodigiService;
    }

    /**
     * Submit an order to Prodigi for fulfillment
     * 
     * @throws \Exception
     */
    public function submitOrderToProdigi(Order $order): array
    {
        // Get print items only (filter out digital products)
        $printItems = $order->items()
            ->whereHas('product', function ($query) {
                $query->where('type', 'like', 'print%')
                    ->orWhereNotNull('prodigi_sku');
            })
            ->get();

        if ($printItems->isEmpty()) {
            throw new \Exception('No print items in order to submit to Prodigi');
        }

        $orderPayload = $this->buildProdigiOrderPayload($order, $printItems);

        // Submit to Prodigi
        $response = $this->prodigiService->createOrder($orderPayload);

        // v4 returns {"outcome": "Created", "order": {"id", "status": {"stage"}, "charges"}}.
        $prodigiOrder = $response['order'] ?? $response;
        $order->update([
            'prodigi_order_id' => $prodigiOrder['id'] ?? null,
            'prodigi_status' => self::statusFrom($prodigiOrder) ?? 'pending',
            // What Prodigi charged us, summed over its charges (may be empty until it's billed).
            'prodigi_charge' => collect($prodigiOrder['charges'] ?? [])->sum(fn ($c) => (float) data_get($c, 'totalCost.amount', 0)) ?: null,
        ]);

        Log::info('Order submitted to Prodigi', [
            'order_id' => $order->id,
            'prodigi_order_id' => $prodigiOrder['id'] ?? null,
        ]);

        return $response;
    }

    /**
     * Build the Prodigi order payload from our order
     */
    private function buildProdigiOrderPayload(Order $order, $printItems): array
    {
        // Get customer address - you may need to adjust based on where you store this
        $recipientAddress = $this->getRecipientAddress($order);

        // Prodigi v4 order. The idempotency key is the order id alone, so a
        // retried submission can never create (and charge for) a second order.
        return [
            'merchantReference' => (string) $order->id,
            'shippingMethod' => config('prints.shipping_method', 'Standard'),
            'idempotencyKey' => 'portfolio-order-'.$order->id,
            'recipient' => [
                'name' => $order->customer_name ?: 'Customer',
                'email' => $order->customer_email,
                'phoneNumber' => $this->getCustomerPhone($order),
                'address' => $recipientAddress,
            ],
            'items' => $this->buildLineItems($printItems),
            'metadata' => ['app' => 'portfolio', 'order_id' => (string) $order->id],
        ];
    }

    /**
     * Build line items for Prodigi order
     */
    private function buildLineItems($printItems): array
    {
        return $printItems->map(function (OrderItem $item) {
            $product = $item->product;

            $line = [
                'merchantReference' => 'item-'.$item->id,
                'sku' => $this->mapProductToProdigiSku($product),
                'copies' => (int) $item->quantity,
                'sizing' => 'fillPrintArea',
                'assets' => [
                    ['printArea' => 'default', 'url' => $this->getProductImageUrl($product)],
                ],
            ];
            if (! empty($item->prodigi_attributes)) {
                $line['attributes'] = $item->prodigi_attributes;
            }

            return $line;
        })->toArray();
    }

    /**
     * Map our product to a Prodigi SKU
     * Adjust this based on your product types and Prodigi catalog
     */
    private function mapProductToProdigiSku($product): string
    {
        if (!empty($product->prodigi_sku)) {
            return $product->prodigi_sku;
        }

        // Example mapping - you'll need to customize based on Prodigi's actual SKUs
        $skuMap = [
            'print_poster' => 'POSTER_A2_GLOSS', // Adjust to actual Prodigi SKUs
            'print_mug' => 'MUG_CERAMIC_11OZ',
            'print_canvas' => 'CANVAS_12X16_GLOSS',
        ];

        return $skuMap[$product->type] ?? 'POSTER_A2_GLOSS';
    }

    /**
     * Get the product image URL for printing
     */
    private function getProductImageUrl($product): string
    {
        // The full-resolution original. Prodigi downloads it after the order
        // is placed, so the link lasts a week (the longest S3 allows).
        $key = $product->photo?->url;
        if (! $key) {
            throw new \Exception('Order item has no photo to print');
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($key, now()->addDays(7));
    }

    private function getRecipientAddress(Order $order): array
    {
        $required = [
            'shipping_address_line1',
            'shipping_city',
            'shipping_county',
            'shipping_postcode',
            'shipping_country_code',
        ];

        foreach ($required as $field) {
            if (blank($order->{$field})) {
                throw new \Exception("Missing required shipping field: {$field}");
            }
        }

        return array_filter([
            'line1' => $order->shipping_address_line1,
            'line2' => $order->shipping_address_line2,
            'postalOrZipCode' => $order->shipping_postcode,
            'countryCode' => strtoupper($order->shipping_country_code),
            'townOrCity' => $order->shipping_city,
            'stateOrCounty' => $order->shipping_county,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function getCustomerPhone(Order $order): string
    {
        if (blank($order->customer_phone)) {
            throw new \Exception('Missing required customer phone on order');
        }

        return $order->customer_phone;
    }

    /**
     * Update order status from Prodigi
     */
    public function syncOrderStatus(Order $order): void
    {
        if (!$order->prodigi_order_id) {
            return;
        }

        $response = $this->prodigiService->getOrderStatus($order->prodigi_order_id);

        $stage = self::statusFrom($response['order'] ?? $response);
        if ($stage === null) {
            return;
        }
        $order->update(['prodigi_status' => $stage]);

        Log::info('Synced Prodigi order status', [
            'order_id' => $order->id,
            'prodigi_status' => $stage,
        ]);
    }

    /**
     * Our status for a Prodigi v4 order: its stage (InProgress, Complete,
     * Cancelled) mapped to the orders.prodigi_status values (an enum).
     */
    public static function statusFrom(array $prodigiOrder): ?string
    {
        $stage = data_get($prodigiOrder, 'status.stage') ?? (is_string($prodigiOrder['status'] ?? null) ? $prodigiOrder['status'] : null);

        return match (strtolower((string) $stage)) {
            '' => null,
            'complete', 'completed', 'shipped' => 'shipped',
            'cancelled', 'canceled' => 'cancelled',
            'pending', 'received', 'validated', 'failed' => strtolower((string) $stage),
            default => 'processing', // InProgress and anything new
        };
    }
}
