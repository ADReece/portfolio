<?php

namespace App\Console\Commands;

use App\Models\ProdigiProduct;
use App\Prints\MockupGenerator;
use Illuminate\Console\Command;

/**
 * Draws mockups for catalogue products (our own images; Prodigi supplies
 * none). Products of the same shape share one file, so this is quick.
 *
 *   php artisan prodigi:mockups            # products without a mockup
 *   php artisan prodigi:mockups --all      # every product (after style changes)
 */
class GenerateProdigiMockups extends Command
{
    protected $signature = 'prodigi:mockups
        {--all : Redraw every product\'s mockup, not just those without one}
        {--sku=* : Only these SKUs}';

    protected $description = 'Generate catalogue product mockups';

    public function handle(MockupGenerator $mockups): int
    {
        $products = ProdigiProduct::query()
            ->when($this->option('sku'), fn ($q, $skus) => $q->whereIn('sku', array_map('strtoupper', $skus)))
            ->when(! $this->option('all') && ! $this->option('sku'), fn ($q) => $q->whereNull('mockup_path'))
            ->orderBy('id')->get();

        $made = $skipped = $failed = 0;
        $drawn = [];
        $bar = $this->output->createProgressBar($products->count());
        foreach ($products as $product) {
            try {
                // --all redraws each shared file once, then reuses it.
                $path = $mockups->generate($product, $this->option('all') && ! isset($drawn[$mockups->keyFor($product)]));
                $drawn[$mockups->keyFor($product)] = true;
                $path ? $made++ : $skipped++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("{$product->sku}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Mockups for {$made} ".\Illuminate\Support\Str::plural('product', $made)." ({$skipped} without a mockup style, e.g. cards or gifts, keep their drawing; {$failed} failed).");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
