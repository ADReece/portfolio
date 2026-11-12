<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Font extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'user_id',
        'name',
        'filename',
        'path',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns a URL for the font file. Used internally for downloading
     * the font in jobs — not exposed to the browser directly.
     */
    public function storageUrl(): string
    {
        return Storage::disk('s3')->temporaryUrl($this->path, now()->addHour());
    }

    /**
     * Download the font to a local temp file and return the path.
     * Caller is responsible for deleting the temp file after use.
     */
    public function downloadToTemp(): string
    {
        $ext      = pathinfo($this->filename, PATHINFO_EXTENSION);
        $tmpPath  = sys_get_temp_dir() . '/font_' . $this->id . '.' . $ext;

        if (!file_exists($tmpPath)) {
            $contents = Storage::disk('s3')->get($this->path);
            file_put_contents($tmpPath, $contents);
        }

        return $tmpPath;
    }

    /**
     * Scopes to fonts available for the given user:
     * all system fonts (user_id = null) + the user's own fonts.
     */
    public function scopeAvailableFor($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        })->where('active', true);
    }
}
