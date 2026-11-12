<?php

namespace Tests\Feature\Videos;

use App\Videos\VideoProbe;

/** A 2-minute H.264 film, without running ffmpeg. */
class FakeProbe implements VideoProbe
{
    public string $codec = 'h264';

    public function probe(string $url): array
    {
        return ['codec' => $this->codec, 'width' => 1920, 'height' => 1080, 'duration' => 125.2];
    }

    public function poster(string $url, float $seconds): string
    {
        return "\xFF\xD8poster";
    }
}
