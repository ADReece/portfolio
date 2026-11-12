<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A film or reel in a set (Videographer plan). */
class Video extends Model
{
    use UsesUuid;

    public const UPLOADING = 'uploading';
    public const PROCESSING = 'processing';
    public const READY = 'ready';
    public const FAILED = 'failed';

    protected $fillable = ['user_id', 'set_id', 'title', 'original_filename', 'key', 'mime', 'size', 'status', 'sort_order'];

    protected $casts = ['size' => 'integer', 'duration' => 'integer', 'width' => 'integer', 'height' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', self::READY);
    }

    public function isReady(): bool
    {
        return $this->status === self::READY;
    }

    public function displayTitle(): string
    {
        return $this->title ?: pathinfo((string) $this->original_filename, PATHINFO_FILENAME) ?: 'Film';
    }

    /** A signed link to the film, for the player (range requests work). */
    public function playbackUrl(): string
    {
        return Storage::disk('s3')->temporaryUrl($this->key, now()->addMinutes(config('videos.playback_minutes')));
    }

    public function posterUrl(): ?string
    {
        return $this->poster_key
            ? Storage::disk('s3')->temporaryUrl($this->poster_key, now()->addMinutes(config('videos.playback_minutes')))
            : null;
    }

    public function durationLabel(): ?string
    {
        if (! $this->duration) {
            return null;
        }

        $d = $this->duration;

        return $d >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($d, 3600), intdiv($d % 3600, 60), $d % 60)
            : sprintf('%d:%02d', intdiv($d, 60), $d % 60);
    }
}
