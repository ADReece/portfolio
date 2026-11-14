<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

/** Makes the web-sized copy of a photo uploaded before display copies existed. */
class MakeDisplayCopy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 80;

    public int $backoff = 60;

    public function __construct(public string $photoId)
    {
        // After everything else: new uploads are never kept waiting behind a backfill.
        $this->onQueue('background');
    }

    public function handle(): void
    {
        $photo = Photo::find($this->photoId);
        // Photos stored outside photos/ have no separate key for a copy: leave them be.
        if (! $photo || $photo->has_display || $photo->status !== Photo::READY || ! str_starts_with((string) $photo->url, 'photos/')) {
            return;
        }

        $original = ProcessImageUpload::downloadOriginal($photo->url);
        try {
            Storage::disk('s3')->put(ProcessImageUpload::derivativeKey($photo->url, 'display'), ProcessImageUpload::makeDisplay($original));
            [$width, $height] = ProcessImageUpload::originalSize($original);
        } finally {
            @unlink($original);
        }

        Photo::whereKey($photo->id)->update(['has_display' => true] + ($width && $height ? ['width' => $width, 'height' => $height] : []));
    }
}
