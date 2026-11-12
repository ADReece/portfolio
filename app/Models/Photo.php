<?php

namespace App\Models;

use App\Models\Traits\ScopesPublic;
use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory, UsesUuid, ScopesPublic;

    protected $fillable = [
        'user_id',
        'set_id',
        'caption',
        'description',
        'url',
        'public',
        'private',
        'size',
        'watermarked',
        'purchased',
        'hide_from_portfolio',
        'status',
        'processing_error',
    ];

    public const PROCESSING = 'processing';
    public const READY = 'ready';
    public const FAILED = 'failed';

    public const FAILED_MESSAGE = "This photo couldn't be processed. Try a smaller file, or save it as a JPEG and upload it again.";

    /** Photos whose thumbnail and watermarked copy exist (safe to show publicly). */
    public function scopeReady($query)
    {
        return $query->where('photos.status', self::READY);
    }

    public function isReady(): bool
    {
        return ($this->status ?? self::READY) === self::READY;
    }

    protected $casts = [
        'credit' => 'array',
        'private' => 'boolean',
        'watermarked' => 'boolean',
        'purchased' => 'boolean',
        'hide_from_portfolio' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function($model){
            if (is_null($model->sort_order) && $model->set_id) {
                $max = Photo::where('set_id',$model->set_id)->max('sort_order');
                $model->sort_order = is_null($max) ? 1 : $max + 1;
            }
        });
    }

    public function getUri() : string
    {
        // Show watermarked version ONLY if:
        // 1. Photo is explicitly marked as watermarked, OR
        // 2. Photo's set's collection is explicitly marked as watermarked
        // Note: Private collections do NOT automatically show watermarked versions
        $shouldWatermark = $this->watermarked ||
                          ($this->set && $this->set->collection && $this->set->collection->watermarked);

        return $shouldWatermark ? $this->getAwsWatermarked() : $this->getAwsMedia();
    }

    public function shouldShowWatermark() : bool
    {
        // Only show watermark if explicitly marked, not based on privacy
        return $this->watermarked ||
               ($this->set && $this->set->collection && $this->set->collection->watermarked);
    }

    public function getAwsMedia()
    {
        return $this->s3Url($this->url);
    }

    public function getAwsThumbnail()
    {
        $key = str_replace('photos/', 'thumbnails/', $this->url);
        return $this->s3Url($key);
    }

    public function getAwsWatermarked()
    {
        $key = str_replace('photos/', 'watermarked/', $this->url);
        return $this->s3Url($key);
    }

    private function s3Url(string $key): string
    {
        // Always signed: the bucket (or the local /media route) is private.
        return Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(10));
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function set() : BelongsTo
    {
        return $this->belongsTo(Set::class);
    }
}
