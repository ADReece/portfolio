<?php

namespace App\Console\Commands;

use App\Jobs\ImportProdigiProduct;
use App\Models\ProdigiImport;
use App\Prints\CatalogueImporter;
use App\Service\ProdigiService;
use Illuminate\Console\Command;

/**
 * Bulk-adds Prodigi products to the print catalogue (as inactive), from a CSV,
 * a list of SKUs, or a discovery scan of known SKU patterns:
 *
 *   php artisan prodigi:import --discover
 *   php artisan prodigi:import --discover --family=FAP --family=CAN
 *   php artisan prodigi:import storage/app/prodigi-prices.csv
 *   php artisan prodigi:import --sku=GLOBAL-FAP-16X24 --sku=GLOBAL-CAN-16X20
 *
 * By default the SKUs are queued (progress shows at /admin/print-catalogue);
 * --now checks them here instead, printing each result.
 */
class ImportProdigiCatalog extends Command
{
    protected $signature = 'prodigi:import
        {file? : A CSV with a "SKU" column (e.g. a Prodigi dashboard price sheet)}
        {--sku=* : SKUs to add}
        {--discover : Try each family in config/prints.php in every common size}
        {--family=* : With --discover, only these families (e.g. FAP, CAN, CFPM)}
        {--now : Check the SKUs in this process instead of the queue}';

    protected $description = 'Add Prodigi products to the print catalogue in bulk (as inactive)';

    public function handle(CatalogueImporter $importer): int
    {
        if (! ProdigiService::configured()) {
            $this->error('PRODIGI_API_KEY is not set.');

            return self::FAILURE;
        }

        $entries = [];
        if ($file = $this->argument('file')) {
            if (! is_readable($file)) {
                $this->error("Can't read {$file}.");

                return self::FAILURE;
            }
            $entries += CatalogueImporter::skusFromCsv($file);
        }
        if ($this->option('sku')) {
            $entries += CatalogueImporter::skusFromText(implode("\n", $this->option('sku')));
        }
        if ($this->option('discover')) {
            $known = array_keys(config('prints.discovery.families'));
            $families = array_map('strtoupper', $this->option('family'));
            if ($unknown = array_diff($families, $known)) {
                $this->error('Unknown families: '.implode(', ', $unknown).'. Known: '.implode(', ', $known).'.');

                return self::FAILURE;
            }
            $entries += CatalogueImporter::discoveryCandidates($families);
        }
        if (! $entries) {
            $this->error('Nothing to import: give a CSV file, --sku, or --discover.');

            return self::FAILURE;
        }

        $source = $this->option('discover') ? 'discover' : ($file ? 'csv' : 'paste');
        $label = 'Command line'.($file ? ': '.basename($file) : '');

        if (! $this->option('now')) {
            $import = $importer->queue($entries, $source, $label);
            $this->info(($import->total - $import->skipped)." SKUs queued ({$import->skipped} already in the catalogue). Progress: /admin/print-catalogue");

            return self::SUCCESS;
        }

        $import = ProdigiImport::create(['source' => $source, 'label' => $label, 'total' => count($entries)]);
        $bar = $this->output->createProgressBar(count($entries));
        foreach (array_keys($entries) as $sku) {
            $job = new ImportProdigiProduct($import->id, $sku, $entries[$sku]['name'] ?? null, $entries[$sku]['category'] ?? null);
            try {
                app()->call([$job, 'handle']);
            } catch (\Throwable $e) {
                $job->failed($e);
                $this->newLine();
                $this->warn("{$sku}: {$e->getMessage()}");
            }
            $bar->advance();
            usleep((int) (60_000_000 / max(1, (int) config('prints.import_per_minute'))));
        }
        $bar->finish();
        $this->newLine();

        $import->refresh();
        $this->info("Added {$import->added}, already there {$import->skipped}, not on Prodigi {$import->not_found}, failed {$import->failed}.");

        return $import->failed ? self::FAILURE : self::SUCCESS;
    }
}
