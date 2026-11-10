<?php

namespace App\Console\Commands;

use App\Models\ProdigiProduct;
use App\Models\User;
use App\Prints\CatalogueRefresher;
use App\Service\ProdigiService;
use Illuminate\Console\Command;

/**
 * Refreshes the admin-curated print catalogue from Prodigi: each product's
 * details and a UK cost quote (both free; no orders are placed). Prodigi v4
 * has no "list the whole catalogue" endpoint, so products are added by SKU at
 * /admin/print-catalogue and this keeps them up to date.
 */
class SyncProdigiCatalog extends Command
{
    protected $signature = 'prodigi:sync-catalog
        {--sku=* : Only these SKUs}
        {--seed-user-preferences : Give photographers a setting for active products they have none for yet (enabled, default price)}';

    protected $description = 'Refresh catalogue products\' details and costs from Prodigi';

    public function handle(): int
    {
        if (! ProdigiService::configured()) {
            $this->warn('PRODIGI_API_KEY is not set; nothing refreshed.');

            return self::SUCCESS;
        }

        $refresher = app(CatalogueRefresher::class);
        $failed = 0;
        $products = ProdigiProduct::query()
            ->when($this->option('sku'), fn ($q, $skus) => $q->whereIn('sku', $skus))
            ->orderBy('id')->get();

        foreach ($products as $product) {
            $refresher->refresh($product);
            if ($product->refresh_error) {
                $failed++;
                $this->warn("{$product->sku}: {$product->refresh_error}");
            }
        }

        $this->info('Refreshed '.($products->count() - $failed)." of {$products->count()} products.");

        if ($this->option('seed-user-preferences')) {
            $this->seedUserPreferences();
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** Adds missing preferences only; never changes a photographer's choices. */
    private function seedUserPreferences(): void
    {
        $productIds = ProdigiProduct::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (! $productIds) {
            return;
        }

        User::query()->select('id')->chunkById(100, function ($users) use ($productIds) {
            foreach ($users as $user) {
                $existing = $user->prodigiProducts()->pluck('prodigi_products.id')->map(fn ($id) => (int) $id)->all();
                $missing = array_diff($productIds, $existing);
                if ($missing) {
                    $user->prodigiProducts()->attach(array_fill_keys($missing, ['is_enabled' => true, 'retail_price' => null]));
                }
            }
        });
    }
}
