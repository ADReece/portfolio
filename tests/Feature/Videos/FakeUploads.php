<?php

namespace Tests\Feature\Videos;

use App\Videos\MultipartUploads;

/** Records multipart calls instead of talking to a bucket. */
class FakeUploads implements MultipartUploads
{
    public array $created = [];
    public array $completed = [];
    public array $aborted = [];

    public function available(): bool
    {
        return true;
    }

    public function create(string $key, string $contentType): string
    {
        $this->created[] = [$key, $contentType];

        return 'up-'.count($this->created);
    }

    public function partUrls(string $key, string $uploadId, array $partNumbers): array
    {
        return collect($partNumbers)->mapWithKeys(fn ($n) => [$n => "https://bucket.test/{$key}?partNumber={$n}&uploadId={$uploadId}"])->all();
    }

    public function complete(string $key, string $uploadId, array $parts): void
    {
        $this->completed[] = [$key, $uploadId, $parts];
    }

    public function abort(string $key, string $uploadId): void
    {
        $this->aborted[] = [$key, $uploadId];
    }
}
