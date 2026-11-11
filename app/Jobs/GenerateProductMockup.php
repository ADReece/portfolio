<?php

namespace App\Jobs;

use App\Models\ProdigiProduct;
use App\Prints\MockupGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Draws one catalogue product's mockup (shared with products of the same shape). */
class GenerateProductMockup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public int $productId, public bool $force = false) {}

    public function handle(MockupGenerator $mockups): void
    {
        if ($product = ProdigiProduct::find($this->productId)) {
            $mockups->generate($product, $this->force);
        }
    }
}
