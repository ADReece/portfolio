<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Template extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'overlay_path',
        'psd_path',
        'fields',
        'active',
    ];

    protected $casts = [
        'fields' => 'array',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedPrints(): HasMany
    {
        return $this->hasMany(GeneratedPrint::class);
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(Set::class, 'set_templates')->withTimestamps();
    }

    public function overlayUrl(): ?string
    {
        if (!$this->overlay_path) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl($this->overlay_path, now()->addMinutes(10));
    }

    /**
     * Return fields array, guaranteed to be an array even if null in DB.
     */
    public function getFieldsAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }
}
