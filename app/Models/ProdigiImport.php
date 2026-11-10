<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A bulk add to the print catalogue, processed one SKU per queued job
 * (App\Jobs\ImportProdigiProduct). Each job records its outcome here.
 */
class ProdigiImport extends Model
{
    /** SKUs kept per list, so a big discovery scan doesn't store hundreds. */
    public const KEEP_SKUS = 100;

    protected $fillable = ['source', 'label', 'total', 'added', 'skipped', 'not_found', 'failed', 'not_found_skus', 'failed_skus', 'finished_at'];

    protected $casts = [
        'not_found_skus' => 'array',
        'failed_skus' => 'array',
        'finished_at' => 'datetime',
    ];

    public function processed(): int
    {
        return $this->added + $this->skipped + $this->not_found + $this->failed;
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /** Records one SKU's outcome: added, skipped, not_found or failed. */
    public static function record(int $id, string $outcome, string $sku): void
    {
        DB::transaction(function () use ($id, $outcome, $sku) {
            $import = static::query()->lockForUpdate()->find($id);
            if (! $import) {
                return;
            }
            $import->{$outcome}++;
            if (in_array($outcome, ['not_found', 'failed'], true)) {
                $list = $import->{$outcome.'_skus'} ?? [];
                if (count($list) < self::KEEP_SKUS) {
                    $list[] = $sku;
                    $import->{$outcome.'_skus'} = $list;
                }
            }
            if ($import->processed() >= $import->total && ! $import->finished_at) {
                $import->finished_at = now();
            }
            $import->save();
        });
    }
}
