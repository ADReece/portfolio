<?php

namespace App\Videos;

/** Browser-to-bucket multipart uploads with presigned part URLs. */
interface MultipartUploads
{
    /** Whether uploads can go straight to the bucket (an S3 disk is set up). */
    public function available(): bool;

    /** Starts an upload; returns its id. */
    public function create(string $key, string $contentType): string;

    /** @return array<int, string> presigned PUT URL per part number */
    public function partUrls(string $key, string $uploadId, array $partNumbers): array;

    /** @param list<array{PartNumber: int, ETag: string}> $parts */
    public function complete(string $key, string $uploadId, array $parts): void;

    public function abort(string $key, string $uploadId): void;
}
