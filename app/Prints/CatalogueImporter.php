<?php

namespace App\Prints;

use App\Jobs\ImportProdigiProduct;
use App\Models\ProdigiImport;
use App\Models\ProdigiProduct;
use Illuminate\Support\Str;

/**
 * Bulk adds to the print catalogue. Prodigi's API can't list its catalogue or
 * an account's products, so SKUs come from pasted text, a CSV (such as the
 * price sheets from Prodigi's dashboard), or a discovery scan of known SKU
 * patterns. Each new SKU is looked up and quoted by a queued job, added as
 * inactive, so nothing reaches photographers until an admin activates it.
 */
class CatalogueImporter
{
    public const SKU_PATTERN = '/^[A-Z0-9][A-Z0-9._\-]*$/';

    /** SKUs from free text: separated by new lines, spaces, commas or semicolons. */
    public static function skusFromText(string $text): array
    {
        return collect(preg_split('/[\s,;]+/', strtoupper($text)))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => $s !== '' && preg_match(self::SKU_PATTERN, $s))
            ->unique()->values()
            ->mapWithKeys(fn ($sku) => [$sku => []])
            ->all();
    }

    /**
     * SKUs (with a name and category, when the sheet has them) from a CSV. The
     * header row can be anywhere in the first rows and needs a column whose
     * heading mentions "SKU" (or "product code"). Without one, every cell that
     * looks like a Prodigi SKU (e.g. GLOBAL-FAP-16X24) is used.
     *
     * @return array<string, array{name?: string, category?: string}>
     */
    public static function skusFromCsv(string $path): array
    {
        $rows = self::readCsv($path);
        $header = null;
        foreach (array_slice($rows, 0, 25, true) as $i => $row) {
            foreach ($row as $col => $cell) {
                if (preg_match('/^\s*([\w ]*\bsku\b[\w ()]*|product\s*code)\s*$/i', $cell)) {
                    $header = $i;
                    $skuCol = $col;
                    break 2;
                }
            }
        }

        $entries = [];
        if ($header === null) {
            foreach ($rows as $row) {
                foreach ($row as $cell) {
                    $cell = strtoupper(trim($cell));
                    if (self::looksLikeSku($cell)) {
                        $entries[$cell] ??= [];
                    }
                }
            }

            return $entries;
        }

        $nameCol = $categoryCol = null;
        foreach ($rows[$header] as $col => $heading) {
            if ($col === $skuCol) {
                continue;
            }
            if ($nameCol === null && preg_match('/^\s*(product\s*)?(name|title|description)\s*$/i', $heading)) {
                $nameCol = $col;
            } elseif ($categoryCol === null && preg_match('/^\s*(product\s*)?(category|type|range)\s*$/i', $heading)) {
                $categoryCol = $col;
            }
        }

        foreach (array_slice($rows, $header + 1) as $row) {
            $sku = strtoupper(trim($row[$skuCol] ?? ''));
            if ($sku === '' || ! preg_match(self::SKU_PATTERN, $sku) || isset($entries[$sku])) {
                continue;
            }
            $entries[$sku] = array_filter([
                'name' => $nameCol !== null ? Str::limit(trim($row[$nameCol] ?? ''), 250, '') : null,
                'category' => $categoryCol !== null ? self::matchCategory(trim($row[$categoryCol] ?? '')) : null,
            ]);
        }

        return $entries;
    }

    /**
     * Candidate SKUs for a discovery scan: GLOBAL-{family}-{size} for each
     * chosen family (all by default), sizes in both orientations.
     *
     * @return array<string, array{}>
     */
    public static function discoveryCandidates(array $families = []): array
    {
        $config = config('prints.discovery');
        $families = $families ?: array_keys($config['families']);
        $sizes = [];
        foreach ($config['inch_sizes'] as $size) {
            [$w, $h] = explode('X', strtoupper($size));
            $sizes[] = "{$w}X{$h}";
            $sizes[] = "{$h}X{$w}";
        }
        $sizes = array_unique(array_merge($sizes, $config['paper_sizes']));

        $skus = [];
        foreach ($families as $family) {
            foreach ($sizes as $size) {
                $skus['GLOBAL-'.strtoupper($family).'-'.$size] = [];
            }
        }

        return $skus;
    }

    /**
     * Queues a lookup for each SKU not already in the catalogue, spread out to
     * config('prints.import_per_minute'). Returns the import, whose counts
     * fill in as the jobs run.
     *
     * @param  array<string, array{name?: string, category?: string}>  $entries
     */
    public function queue(array $entries, string $source, ?string $label = null): ProdigiImport
    {
        $entries = array_slice($entries, 0, (int) config('prints.import_max_skus'), true);
        $existing = ProdigiProduct::query()->whereIn('sku', array_keys($entries))->pluck('sku')->all();
        $new = array_diff_key($entries, array_flip($existing));

        $import = ProdigiImport::create([
            'source' => $source,
            'label' => $label,
            'total' => count($entries),
            'skipped' => count($existing),
            'finished_at' => $new ? null : now(),
        ]);

        $gap = 60 / max(1, (int) config('prints.import_per_minute'));
        $i = 0;
        foreach ($new as $sku => $details) {
            ImportProdigiProduct::dispatch($import->id, $sku, $details['name'] ?? null, $details['category'] ?? null)
                ->delay(now()->addMilliseconds((int) round($i++ * $gap * 1000)));
        }

        return $import;
    }

    /** Adds a looked-up product, inactive, then quotes its cost. */
    public static function add(array $lookup, ?string $name = null, ?string $category = null): ProdigiProduct
    {
        $product = ProdigiProduct::create([
            'sku' => $lookup['sku'],
            'name' => Str::limit($name ?: ($lookup['description'] ?: $lookup['sku']), 250, ''),
            'category' => $category ?: ProdigiProduct::guessCategory($lookup['sku'], $lookup['description']),
            'description' => $lookup['description'],
            'size_label' => $lookup['size_label'],
            'raw_payload' => $lookup['raw'],
            'sort_order' => (int) ProdigiProduct::max('sort_order') + 10,
            'is_active' => false,
        ]);

        app(CatalogueRefresher::class)->refresh($product);
        \App\Jobs\GenerateProductMockup::dispatch($product->id);

        return $product;
    }

    private static function looksLikeSku(string $cell): bool
    {
        return strlen($cell) >= 6 && strlen($cell) <= 60
            && preg_match('/^[A-Z][A-Z0-9]*(-[A-Z0-9.]+){2,}$/', $cell)
            && preg_match('/\d/', $cell);
    }

    private static function matchCategory(string $value): ?string
    {
        foreach (ProdigiProduct::CATEGORIES as $category) {
            if (strcasecmp($category, $value) === 0) {
                return $category;
            }
        }

        return null; // guessed from the SKU instead
    }

    /** @return list<list<string>> */
    private static function readCsv(string $path): array
    {
        $first = (string) file_get_contents($path, false, null, 0, 4096);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($first, $d))->first();

        $rows = [];
        $handle = fopen($path, 'r');
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = array_map(fn ($c) => preg_replace('/^\xEF\xBB\xBF/', '', (string) $c), $row);
            if (count($rows) >= 20000) {
                break;
            }
        }
        fclose($handle);

        return $rows;
    }
}
