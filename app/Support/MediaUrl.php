<?php

namespace App\Support;

use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

/**
 * Links to stored photos and images. Always signed (the bucket, or the local
 * /media route, is private), but the same link is handed out for a few hours
 * at a time (config media.url_window_hours), so browsers can cache images
 * instead of downloading them again on every page view.
 */
class MediaUrl
{
    public static function signed(string $key): string
    {
        $hours = max(1, (int) config('media.url_window_hours', 6));
        $now = now()->utc();
        $start = $now->copy()->startOfDay()->addHours(intdiv($now->hour, $hours) * $hours);
        $expires = $start->copy()->addHours($hours * 2); // always at least one window left
        $disk = Storage::disk('s3');

        if ($disk instanceof AwsS3V3Adapter) {
            return $disk->temporaryUrl($key, $expires, [
                'start_time' => $start->getTimestamp(), // signed as of the window's start
                'ResponseCacheControl' => 'private, max-age='.($hours * 3600),
            ]);
        }

        return $disk->temporaryUrl($key, $expires);
    }
}
