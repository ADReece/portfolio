<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Collection extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'name',
        'user_id',
        'status',
        'event_date',
        'cover_photo_id',
        'private',
        'password',
        'watermarked',
        'hide_from_portfolio',
        'cover_photo_object_position',
        'digital_sales',
        'digital_photo_price',
        'digital_collection_price',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'private' => 'boolean',
        'watermarked' => 'boolean',
        'hide_from_portfolio' => 'boolean',
        'digital_sales' => 'boolean',
        'digital_photo_price' => 'decimal:2',
        'digital_collection_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function($model){
            if (is_null($model->sort_order)) {
                // Assign next max+1 for user's collections if user_id present
                if ($model->user_id) {
                    $max = Collection::where('user_id',$model->user_id)->max('sort_order');
                    $model->sort_order = is_null($max) ? 1 : $max + 1;
                }
            }
        });
    }

    /**
     * CSS object-position for the cover photo, from the stored focal point
     * (center, top, bottom, top-right, bottom-right).
     */
    public function coverObjectPosition(): string
    {
        return match ($this->cover_photo_object_position) {
            'top' => 'center top',
            'bottom' => 'center bottom',
            'top-right' => 'right top',
            'bottom-right' => 'right bottom',
            'top-left' => 'left top',
            'bottom-left' => 'left bottom',
            default => 'center center',
        };
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sets() : HasMany
    {
        return $this->hasMany(Set::class);
    }

    /** All photos in the collection, across its sets. */
    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(Photo::class, Set::class);
    }

    public function coverPhoto() : BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }
}
