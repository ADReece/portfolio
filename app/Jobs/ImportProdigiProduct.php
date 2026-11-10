<?php

namespace App\Jobs;

use App\Models\ProdigiImport;
use App\Models\ProdigiProduct;
use App\Prints\CatalogueImporter;
use App\Prints\CatalogueRefresher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * One SKU of a bulk catalogue add: looks it up on Prodigi (free), and if it
 * exists adds it as inactive and quotes its cost. Retries when Prodigi can't
 * be reached or rate-limits; an unknown SKU just counts as "not found".
 */
class ImportProdigiProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 60;

    public array $backoff = [30, 120, 600];

    public function __construct(
        public int $importId,
        public string $sku,
        public ?string $name = null,
        public ?string $category = null,
    ) {}

    public function handle(CatalogueRefresher $refresher): void
    {
        if (ProdigiProduct::where('sku', $this->sku)->exists()) {
            ProdigiImport::record($this->importId, 'skipped', $this->sku);

            return;
        }

        $lookup = $refresher->lookup($this->sku); // throws (and retries) if Prodigi can't be reached
        if (! $lookup) {
            ProdigiImport::record($this->importId, 'not_found', $this->sku);

            return;
        }
        // Prodigi may answer for a SKU in another case or form; don't duplicate.
        if ($lookup['sku'] !== $this->sku && ProdigiProduct::where('sku', $lookup['sku'])->exists()) {
            ProdigiImport::record($this->importId, 'skipped', $this->sku);

            return;
        }

        CatalogueImporter::add($lookup, $this->name, $this->category);
        ProdigiImport::record($this->importId, 'added', $this->sku);
    }

    public function failed(?Throwable $e): void
    {
        ProdigiImport::record($this->importId, 'failed', $this->sku);
    }
}
