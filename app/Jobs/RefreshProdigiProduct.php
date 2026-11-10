<?php

namespace App\Jobs;

use App\Models\ProdigiProduct;
use App\Prints\CatalogueRefresher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Re-fetches one catalogue product's details and cost from Prodigi. */
class RefreshProdigiProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // the outcome (or error) is recorded on the product

    public int $timeout = 60;

    public function __construct(public int $productId) {}

    public function handle(CatalogueRefresher $refresher): void
    {
        if ($product = ProdigiProduct::find($this->productId)) {
            $refresher->refresh($product);
        }
    }
}
