<?php

namespace App\Jobs;

use App\Models\Video;
use App\Videos\VideoProbe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * After an upload: checks it's H.264/H.265, records its size and length, and
 * takes a poster frame. No transcoding (the queue has 512 MB).
 */
class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;
    public int $backoff = 60;

    public const UNSUPPORTED = 'This film isn\'t H.264 or H.265 (HEVC). Export it as an MP4 with H.264 and upload it again.';
    public const UNREADABLE = 'This film couldn\'t be read. Export it again as an MP4 (H.264) and upload it.';

    public function __construct(public string $videoId)
    {
        $this->afterCommit();
    }

    public function handle(VideoProbe $probe): void
    {
        $video = Video::find($this->videoId);
        if (! $video || $video->status !== Video::PROCESSING) {
            return;
        }

        $url = Storage::disk('s3')->temporaryUrl($video->key, now()->addMinutes(30));
        $info = $probe->probe($url);
        if (! in_array($info['codec'], config('videos.codecs'), true)) {
            $this->markFailed($video, self::UNSUPPORTED);

            return;
        }

        $at = $info['duration'] ? min(3.0, $info['duration'] / 3) : 1.0;
        $posterKey = preg_replace('#^videos/#', 'video-posters/', preg_replace('/\.[a-z0-9]+$/i', '.jpg', $video->key));
        Storage::disk('s3')->put($posterKey, $probe->poster($url, $at));

        $video->forceFill([
            'status' => Video::READY,
            'poster_key' => $posterKey,
            'codec' => $info['codec'],
            'width' => $info['width'],
            'height' => $info['height'],
            'duration' => $info['duration'] ? (int) round($info['duration']) : null,
            'processing_error' => null,
        ])->save();
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Video processing failed', ['video_id' => $this->videoId, 'error' => $e?->getMessage()]);
        if ($video = Video::find($this->videoId)) {
            $this->markFailed($video, self::UNREADABLE);
        }
    }

    private function markFailed(Video $video, string $message): void
    {
        $video->forceFill(['status' => Video::FAILED, 'processing_error' => $message])->save();
    }
}
