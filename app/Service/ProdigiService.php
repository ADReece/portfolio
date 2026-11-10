<?php

namespace App\Service;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Prodigi Print API v4 client. Authenticates with the X-API-Key header.
 * PRODIGI_ENVIRONMENT=live means every order costs real money: tests must use
 * Http::fake. Quotes and product lookups are free. Never log the API key.
 */
class ProdigiService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.prodigi.api_key');
        $this->baseUrl = rtrim((string) config('services.prodigi.base_url'), '/');

        if ($this->apiKey === '') {
            throw new \Exception('Prodigi API key not configured (PRODIGI_API_KEY)');
        }
    }

    public static function configured(): bool
    {
        return filled(config('services.prodigi.api_key'));
    }

    /**
     * GET /products/{sku}: one product's description, dimensions, attributes,
     * print areas and variants. Returns the "product" object, or null if Prodigi
     * doesn't know the SKU.
     */
    public function getProduct(string $sku): ?array
    {
        $response = $this->client()->get($this->baseUrl.'/products/'.rawurlencode($sku));

        if ($response->status() === 404 || $response->json('outcome') === 'NotFound') {
            return null;
        }
        if (! $response->successful() || ! is_array($response->json('product'))) {
            Log::warning('Prodigi product lookup failed', ['sku' => $sku, 'status' => $response->status()]);

            throw new \Exception('Prodigi product lookup failed (HTTP '.$response->status().')');
        }

        return $response->json('product');
    }

    /**
     * POST /quotes: the cost of $copies copies of $sku shipped to $countryCode. Free;
     * places no order. Returns the matching quote ("costSummary" etc.).
     */
    public function quote(string $sku, array $attributes = [], string $countryCode = 'GB', string $currency = 'GBP', string $shippingMethod = 'Standard', int $copies = 1): array
    {
        $item = ['sku' => $sku, 'copies' => max(1, $copies), 'assets' => [['printArea' => 'default']]];
        if ($attributes) {
            $item['attributes'] = $attributes;
        }

        $response = $this->client()->post($this->baseUrl.'/quotes', [
            'shippingMethod' => $shippingMethod,
            'destinationCountryCode' => $countryCode,
            'currencyCode' => $currency,
            'items' => [$item],
        ]);

        $quotes = $response->json('quotes');
        if (! $response->successful() || ! is_array($quotes) || $quotes === []) {
            Log::warning('Prodigi quote failed', ['sku' => $sku, 'status' => $response->status(), 'outcome' => $response->json('outcome')]);

            throw new \Exception('Prodigi quote failed (HTTP '.$response->status().')');
        }

        return collect($quotes)->first(fn ($q) => strcasecmp((string) ($q['shipmentMethod'] ?? ''), $shippingMethod) === 0) ?? $quotes[0];
    }

    /**
     * Create a new order with Prodigi
     */
    public function createOrder(array $orderData): array
    {
        $response = $this->client()
            ->post($this->baseUrl . '/orders', $orderData);

        if (!$response->successful()) {
            Log::error('Prodigi order creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \Exception(
                'Failed to create Prodigi order: ' . ($response->json('message') ?? $response->json('outcome') ?? 'HTTP '.$response->status())
            );
        }

        Log::info('Prodigi order created successfully', [
            'order_id' => $response->json('id'),
        ]);

        return $response->json();
    }

    /**
     * Get order status from Prodigi
     */
    public function getOrderStatus(string $prodigiOrderId): array
    {
        $response = $this->client()
            ->get($this->baseUrl . '/orders/' . $prodigiOrderId);

        if (!$response->successful()) {
            Log::error('Failed to fetch Prodigi order status', [
                'prodigi_order_id' => $prodigiOrderId,
                'status' => $response->status(),
            ]);

            throw new \Exception('Failed to fetch order status from Prodigi');
        }

        return $response->json();
    }

    /**
     * Cancel a Prodigi order
     */
    public function cancelOrder(string $prodigiOrderId): array
    {
        $response = $this->client()
            ->post($this->baseUrl . '/orders/' . $prodigiOrderId . '/cancel');

        if (!$response->successful()) {
            Log::error('Failed to cancel Prodigi order', [
                'prodigi_order_id' => $prodigiOrderId,
                'status' => $response->status(),
            ]);

            throw new \Exception('Failed to cancel Prodigi order');
        }

        return $response->json();
    }

    /**
     * Get available countries for shipping
     */
    public function getCountries(): array
    {
        $response = $this->client()
            ->get($this->baseUrl . '/countries');

        if (!$response->successful()) {
            Log::error('Failed to fetch Prodigi countries', [
                'status' => $response->status(),
            ]);

            throw new \Exception('Failed to fetch countries from Prodigi');
        }

        return $response->json();
    }

    /**
     * Build the HTTP client with authentication
     */
    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])
            ->asJson()
            ->timeout(30)
            // Retry connection problems only, never a request Prodigi answered:
            // a retried order could otherwise be placed twice.
            ->retry(3, 200, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException, throw: false);
    }
}
