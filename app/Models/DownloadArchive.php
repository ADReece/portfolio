<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * Photos chosen for one ZIP download. Instant downloads stream from the
 * stored list within minutes; background ones are built onto storage by the
 * queue (BuildDownloadArchive) and kept for a couple of days.
 */
class DownloadArchive extends Model
{
    use UsesUuid;

    public const INSTANT = 'instant';
    public const BACKGROUND = 'background';

    public const PENDING = 'pending';
    public const BUILDING = 'building';
    public const READY = 'ready';
    public const FAILED = 'failed';

    protected $guarded = ['id'];

    protected $casts = [
        'files' => 'array',
        'expires_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isReady(): bool
    {
        return $this->status === self::READY && $this->path !== null && ! $this->isExpired();
    }

    /** The archive's page (status, then the download button). Signed: anyone with the link can use it. */
    public function pageUrl(): string
    {
        return URL::temporarySignedRoute('bulk-downloads.archive', $this->expires_at, ['archive' => $this->id]);
    }

    public function emailUrl(): string
    {
        return URL::temporarySignedRoute('bulk-downloads.archive.email', $this->expires_at, ['archive' => $this->id]);
    }

    public function fileUrl(): string
    {
        return URL::temporarySignedRoute('bulk-downloads.archive.file', $this->expires_at, ['archive' => $this->id]);
    }

    public function streamUrl(): string
    {
        return URL::temporarySignedRoute('bulk-downloads.stream', $this->expires_at, ['archive' => $this->id]);
    }
}
