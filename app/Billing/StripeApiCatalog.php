<?php

namespace App\Billing;

use Stripe\StripeClient;

class StripeApiCatalog implements StripeCatalog
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function findProductsByPlanSlug(string $slug): array
    {
        // Listing (not the Search API) so a product created moments ago is
        // found immediately; search results lag by up to a minute.
        $found = [];
        foreach ($this->stripe->products->all(['active' => true, 'limit' => 100])->autoPagingIterator() as $product) {
            if (($product->metadata['plan_slug'] ?? null) === $slug) {
                $found[] = $product->toArray();
            }
        }

        return $found;
    }

    public function createProduct(array $params): array
    {
        return $this->stripe->products->create($params)->toArray();
    }

    public function updateProduct(string $id, array $params): array
    {
        return $this->stripe->products->update($id, $params)->toArray();
    }

    public function activePrices(string $productId): array
    {
        $prices = [];
        foreach ($this->stripe->prices->all(['product' => $productId, 'active' => true, 'type' => 'recurring', 'limit' => 100])->autoPagingIterator() as $price) {
            $prices[] = $price->toArray();
        }

        return $prices;
    }

    public function createPrice(array $params): array
    {
        return $this->stripe->prices->create($params)->toArray();
    }

    public function archivePrice(string $id): void
    {
        $this->stripe->prices->update($id, ['active' => false]);
    }

    public function findCoupon(string $id): ?array
    {
        try {
            return $this->stripe->coupons->retrieve($id)->toArray();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($e->getHttpStatus() === 404) {
                return null;
            }
            throw $e;
        }
    }

    public function createCoupon(array $params): array
    {
        return $this->stripe->coupons->create($params)->toArray();
    }
}
