<?php

namespace App\Models;

use App\Prints\PrintPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * A print product in Portfolio's catalogue, curated by admins at
 * /admin/print-catalogue. Details and cost come from Prodigi by SKU.
 * Inactive products are hidden from photographers and buyers; their pivot
 * rows and past orders are kept.
 */
class ProdigiProduct extends Model
{
    use HasFactory;

    public const CATEGORIES = ['Prints', 'Fine art prints', 'Canvas', 'Framed', 'Wall art', 'Photo books', 'Cards', 'Gifts', 'Other'];

    protected $fillable = [
        'sku',
        'name',
        'category',
        'sort_order',
        'description',
        'size_label',
        'allowed_attributes',
        'image_path',
        'default_retail_price',
        'is_active',
        'raw_payload',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'raw_payload' => 'array',
        'allowed_attributes' => 'array',
        'sort_order' => 'integer',
        'default_retail_price' => 'decimal:2',
        'cost_item' => 'decimal:2',
        'cost_shipping' => 'decimal:2',
        'cost_quoted_at' => 'datetime',
        'refreshed_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'prodigi_product_user')
            ->withPivot('is_enabled', 'retail_price')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCatalogueOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('category')->orderBy('name');
    }

    /** Attribute options Prodigi offers for this product, e.g. ["color" => ["black", "white"]]. */
    public function prodigiAttributes(): array
    {
        $attributes = $this->raw_payload['attributes'] ?? [];

        return collect(is_array($attributes) ? $attributes : [])
            ->filter(fn ($values) => is_array($values) && $values !== [])
            ->map(fn ($values) => array_values(array_map('strval', $values)))
            ->all();
    }

    /** Options offered to clients: the admin's choice, limited to what Prodigi offers. */
    public function offeredAttributes(): array
    {
        $allowed = $this->allowed_attributes ?? [];

        return collect($this->prodigiAttributes())
            ->map(fn ($values, $name) => isset($allowed[$name]) && $allowed[$name] !== []
                ? array_values(array_intersect($values, $allowed[$name]))
                : $values)
            ->filter(fn ($values) => $values !== [])
            ->all();
    }

    /** A complete set of attributes (first offered option of each), for quotes. */
    public function defaultAttributes(): array
    {
        return collect($this->offeredAttributes())->map(fn ($values) => $values[0])->all();
    }

    public function totalCost(): ?float
    {
        return $this->cost_item === null ? null : round((float) $this->cost_item + (float) $this->cost_shipping, 2);
    }

    public function minimumRetailPrice(): ?float
    {
        return app(PrintPricing::class)->minimumFor($this);
    }

    public function defaultPrice(): float
    {
        return app(PrintPricing::class)->defaultFor($this);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return app()->isLocal()
            ? Storage::disk('s3')->url($this->image_path)
            : Storage::disk('s3')->temporaryUrl($this->image_path, now()->addHour());
    }

    /** A starting category from the SKU (then the description); admins can change it. */
    public static function guessCategory(string $sku, ?string $description): string
    {
        $rules = [
            'Canvas' => '/CANVAS|(^|-)(CAN|SLIMCAN|ECO-CAN)(-|$)/',
            'Framed' => '/FRAME|(^|-)(CFP\w*|FRA|BOX|SPACE)(-|$)/',
            'Cards' => '/CARD|(^|-)(GRE|POST)(-|$)/',
            'Photo books' => '/BOOK/',
            'Gifts' => '/MUG|CUSHION|PUZZLE|TOTE|PHONE|COASTER/',
            'Wall art' => '/ACRYLIC|METAL|DIBOND|(^|-)(ACRY|MET|ALU)(-|$)/',
            'Fine art prints' => '/FINE ART|(^|-)(FAP|HPR|HGE|SMG|BAP)(-|$)/',
        ];
        foreach ([strtoupper($sku), strtoupper((string) $description)] as $text) {
            foreach ($rules as $category => $pattern) {
                if (preg_match($pattern, $text)) {
                    return $category;
                }
            }
        }

        return 'Prints';
    }

    public static function labelAttribute(string $name): string
    {
        return ucfirst(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $name)));
    }
}
