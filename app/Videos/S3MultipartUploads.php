<?php

namespace App\Videos;

use Illuminate\Support\Facades\Storage;

/** Multipart uploads on the s3 disk's bucket (Hetzner Object Storage in production). */
class S3MultipartUploads implements MultipartUploads
{
    public function available(): bool
    {
        return config('filesystems.disks.s3.driver') === 's3';
    }

    private function client(): \Aws\S3\S3Client
    {
        return Storage::disk('s3')->getClient();
    }

    private function bucket(): string
    {
        return (string) config('filesystems.disks.s3.bucket');
    }

    public function create(string $key, string $contentType): string
    {
        return $this->client()->createMultipartUpload([
            'Bucket' => $this->bucket(), 'Key' => $key, 'ContentType' => $contentType,
        ])['UploadId'];
    }

    public function partUrls(string $key, string $uploadId, array $partNumbers): array
    {
        $urls = [];
        foreach ($partNumbers as $n) {
            $command = $this->client()->getCommand('UploadPart', [
                'Bucket' => $this->bucket(), 'Key' => $key, 'UploadId' => $uploadId, 'PartNumber' => (int) $n,
            ]);
            $urls[(int) $n] = (string) $this->client()->createPresignedRequest($command, '+2 hours')->getUri();
        }

        return $urls;
    }

    public function complete(string $key, string $uploadId, array $parts): void
    {
        $this->client()->completeMultipartUpload([
            'Bucket' => $this->bucket(), 'Key' => $key, 'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);
    }

    public function abort(string $key, string $uploadId): void
    {
        $this->client()->abortMultipartUpload(['Bucket' => $this->bucket(), 'Key' => $key, 'UploadId' => $uploadId]);
    }
}
