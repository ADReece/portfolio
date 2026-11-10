<?php

namespace App\Support;

/**
 * The self-hosted homepage photos, built by scripts/marketing-images.sh into
 * public/images/marketing, with their sizes and credits in
 * resources/marketing/images.json.
 */
class MarketingImages
{
    private ?array $manifest = null;

    public function __construct(private ?string $path = null)
    {
        $this->path ??= resource_path('marketing/images.json');
    }

    public function all(): array
    {
        if ($this->manifest === null) {
            $this->manifest = is_file($this->path)
                ? (json_decode((string) file_get_contents($this->path), true) ?: [])
                : [];
        }

        return $this->manifest;
    }

    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function url(string $key, int $width, string $format): string
    {
        return asset("images/marketing/{$key}-{$width}.{$format}");
    }

    /** "…-480.avif 480w, …-800.avif 800w, …" */
    public function srcset(string $key, string $format): string
    {
        $image = $this->get($key);

        return collect($image['widths'] ?? [])
            ->map(fn (int $w) => $this->url($key, $w, $format).' '.$w.'w')
            ->implode(', ');
    }

    /** The 800px JPEG, for the <img> fallback and og:image. */
    public function fallback(string $key): ?string
    {
        return $this->get($key) ? $this->url($key, 800, 'jpg') : null;
    }
}
