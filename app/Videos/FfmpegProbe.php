<?php

namespace App\Videos;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * ffprobe/ffmpeg straight from the bucket's signed URL: they read only what
 * they need (the header, then one frame) using range requests, so the film
 * is never downloaded whole and memory stays small.
 */
class FfmpegProbe implements VideoProbe
{
    public function probe(string $url): array
    {
        $result = Process::timeout(120)->run([
            config('videos.ffprobe'), '-v', 'error', '-print_format', 'json', '-show_format', '-show_streams', $url,
        ]);
        if (! $result->successful()) {
            throw new RuntimeException('ffprobe failed: '.trim($result->errorOutput()));
        }
        $data = json_decode($result->output(), true) ?: [];
        $video = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video') ?? [];
        $width = isset($video['width']) ? (int) $video['width'] : null;
        $height = isset($video['height']) ? (int) $video['height'] : null;

        // Phones store portrait films as landscape plus a rotation.
        $rotation = (int) ($video['tags']['rotate'] ?? collect($video['side_data_list'] ?? [])->firstWhere('rotation', '!=', null)['rotation'] ?? 0);
        if ($width && $height && abs($rotation) % 180 === 90) {
            [$width, $height] = [$height, $width];
        }

        return [
            'codec' => $video['codec_name'] ?? null,
            'width' => $width,
            'height' => $height,
            'duration' => isset($data['format']['duration']) ? (float) $data['format']['duration'] : null,
        ];
    }

    public function poster(string $url, float $seconds): string
    {
        $result = Process::timeout(120)->run([
            config('videos.ffmpeg'), '-nostdin', '-loglevel', 'error', '-ss', (string) round($seconds, 2), '-i', $url,
            '-frames:v', '1', '-vf', "scale='min(1280,iw)':-2", '-q:v', '3', '-f', 'image2', '-c:v', 'mjpeg', 'pipe:1',
        ]);
        if (! $result->successful() || $result->output() === '') {
            throw new RuntimeException('ffmpeg poster failed: '.trim($result->errorOutput()));
        }

        return $result->output();
    }
}
