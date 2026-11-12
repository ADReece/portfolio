<?php

namespace App\Videos;

/** Reads a film's details and a poster frame, from a (signed) URL. */
interface VideoProbe
{
    /** @return array{codec: ?string, width: ?int, height: ?int, duration: ?float} */
    public function probe(string $url): array;

    /** A JPEG of the frame at $seconds, at most 1280 px wide. */
    public function poster(string $url, float $seconds): string;
}
