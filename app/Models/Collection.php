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
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'private' => 'boolean',
        'watermarked' => 'boolean',
        'hide_from_portfolio' => 'boolean',
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

    public function coverPhotoPosition()
    {
        return 'object-' . $this->cover_photo_object_position ?? 'center-center';
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
