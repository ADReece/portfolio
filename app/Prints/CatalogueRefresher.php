<?php

namespace App\Prints;

use App\Models\ProdigiProduct;
use App\Service\ProdigiService;
use Carbon\CarbonImmutable;

/**
 * Fetches a product's details and a UK cost quote from Prodigi. Lookups and
 * quotes are free and place no orders.
 */
class CatalogueRefresher
{
    public function __construct(private ProdigiService $prodigi) {}

    /**
     * Details for a SKU, for the admin to review before adding it. Null if
     * Prodigi doesn't know the SKU.
     *
     * @return array{sku: string, description: ?string, size_label: ?string, attributes: array, print_areas: list<string>, variants: int, raw: array}|null
     */
    public function lookup(string $sku): ?array
    {
        $product = $this->prodigi->getProduct($sku);

        return $product ? $this->summarise($product, $sku) : null;
    }

    /** Re-fetches details and cost and records the outcome on the product. */
    public function refresh(ProdigiProduct $product): ProdigiProduct
    {
        try {
            $details = $this->prodigi->getProduct($product->sku);
            if (! $details) {
                throw new \RuntimeException("Prodigi no longer recognises {$product->sku}.");
            }
            $summary = $this->summarise($details, $product->sku);
            $product->raw_payload = $summary['raw'];
            $product->size_label = $summary['size_label'];
            $product->refreshed_at = CarbonImmutable::now();

            $quote = $this->prodigi->quote(
                $product->sku,
                $product->defaultAttributes(),
                config('prints.quote_country'),
                config('prints.quote_currency'),
                config('prints.shipping_method'),
            );
            $items = data_get($quote, 'costSummary.items', []);
            $shipping = data_get($quote, 'costSummary.shipping', []);
            if (! is_numeric($items['amount'] ?? null)) {
                throw new \RuntimeException('Prodigi\'s quote had no item cost.');
            }

            $product->cost_item = (float) $items['amount'];
            $product->cost_shipping = (float) ($shipping['amount'] ?? 0);
            $product->cost_currency = strtoupper((string) ($items['currency'] ?? config('prints.quote_currency')));
            $product->cost_quoted_at = CarbonImmutable::now();
            $product->refresh_error = null;
        } catch (\Throwable $e) {
            $product->refresh_error = $e->getMessage();
        }

        $product->save();

        return $product;
    }

    private function summarise(array $product, string $sku): array
    {
        $dims = $product['productDimensions'] ?? [];
        $size = isset($dims['width'], $dims['height'])
            ? rtrim(rtrim(number_format((float) $dims['width'], 2), '0'), '.').' × '.rtrim(rtrim(number_format((float) $dims['height'], 2), '0'), '.').(isset($dims['units']) ? ' '.$dims['units'] : '')
            : null;

        return [
            'sku' => (string) ($product['sku'] ?? $sku),
            'description' => isset($product['description']) && is_string($product['description']) ? $product['description'] : null,
            'size_label' => $size,
            'attributes' => collect($product['attributes'] ?? [])->filter(fn ($v) => is_array($v))->all(),
            'print_areas' => array_keys(is_array($product['printAreas'] ?? null) ? $product['printAreas'] : []),
            'variants' => is_array($product['variants'] ?? null) ? count($product['variants']) : 0,
            'raw' => $product,
        ];
    }
}
