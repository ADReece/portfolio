<?php

namespace App\Console\Commands;

use App\Models\ProdigiProduct;
use Illuminate\Console\Command;

/**
 * Reports any image-like fields in the product data already fetched from
 * Prodigi (raw_payload), to check whether Prodigi supplies product images we
 * could store. Reads the database only; makes no API calls.
 *
 *   php artisan prodigi:find-images
 */
class FindProdigiImages extends Command
{
    protected $signature = 'prodigi:find-images {--limit=20 : Example values to show}';

    protected $description = 'Look for image URLs in the stored Prodigi product data';

    public function handle(): int
    {
        $keys = [];
        $examples = [];
        $products = 0;
        $withImages = 0;

        ProdigiProduct::query()->select(['id', 'sku', 'raw_payload'])->chunkById(200, function ($chunk) use (&$keys, &$examples, &$products, &$withImages) {
            foreach ($chunk as $product) {
                $products++;
                $found = false;
                $this->walk($product->raw_payload ?? [], '', function (string $path, $value) use (&$keys, &$examples, &$found, $product) {
                    $isUrl = is_string($value) && preg_match('#^https?://#i', $value);
                    $isImageKey = preg_match('/image|thumb|mockup|photo|picture|asset.*url|url/i', $path);
                    if ($isUrl || ($isImageKey && is_string($value) && $value !== '')) {
                        $generic = preg_replace('/\.\d+(?=\.|$)/', '.*', $path);
                        $keys[$generic] = ($keys[$generic] ?? 0) + 1;
                        if (count($examples) < (int) $this->option('limit')) {
                            $examples[] = [$product->sku, $path, \Illuminate\Support\Str::limit((string) $value, 100)];
                        }
                        $found = true;
                    }
                });
                $withImages += $found ? 1 : 0;
            }
        });

        $this->info("Checked {$products} products: {$withImages} have image-like fields.");
        if ($keys) {
            arsort($keys);
            $this->table(['Field', 'Products'], collect($keys)->map(fn ($n, $k) => [$k, $n])->values()->all());
            $this->table(['SKU', 'Field', 'Value'], $examples);
        } else {
            $this->line('Prodigi\'s product data has no image URLs, so there is nothing to import.');
        }

        return self::SUCCESS;
    }

    private function walk(mixed $value, string $path, callable $visit): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $this->walk($child, $path === '' ? (string) $key : $path.'.'.$key, $visit);
            }

            return;
        }
        $visit($path, $value);
    }
}
