<?php

namespace App\Billing;

/**
 * The few Stripe catalogue calls the plan sync needs, so tests can swap in
 * a fake. Products and prices are plain arrays shaped like Stripe's objects.
 */
interface StripeCatalog
{
    /** Active products whose metadata.plan_slug matches, newest first. */
    public function findProductsByPlanSlug(string $slug): array;

    public function createProduct(array $params): array;

    public function updateProduct(string $id, array $params): array;

    /** Active recurring prices of a product. */
    public function activePrices(string $productId): array;

    public function createPrice(array $params): array;

    /** Archive (deactivate) a price; Stripe prices can't be edited or deleted. */
    public function archivePrice(string $id): void;

    /** The coupon with this id, or null if Stripe has none. */
    public function findCoupon(string $id): ?array;

    public function createCoupon(array $params): array;
}
