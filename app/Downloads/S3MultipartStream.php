<?php

namespace App\Downloads;

use Aws\S3\S3ClientInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A write-only stream that uploads to S3 as it's written: it keeps one part
 * (8 MB) in memory and sends it as a multipart upload part when full. So a
 * ZIP of thousands of photos never sits in memory or on disk.
 */
class S3MultipartStream implements StreamInterface
{
    private const PART_SIZE = 8 * 1024 * 1024; // S3's minimum is 5 MB

    private ?string $uploadId = null;

    private string $buffer = '';

    private int $position = 0;

    /** @var list<array{PartNumber: int, ETag: string}> */
    private array $parts = [];

    private bool $closed = false;

    public function __construct(
        private readonly S3ClientInterface $client,
        private readonly string $bucket,
        private readonly string $key,
        private readonly string $contentType,
    ) {}

    public function write(string $string): int
    {
        if ($this->closed) {
            throw new \RuntimeException('The upload has finished.');
        }
        $this->buffer .= $string;
        $this->position += strlen($string);
        while (strlen($this->buffer) >= self::PART_SIZE) {
            $this->uploadPart(substr($this->buffer, 0, self::PART_SIZE));
            $this->buffer = substr($this->buffer, self::PART_SIZE);
        }

        return strlen($string);
    }

    /** Sends what's left and assembles the object. */
    public function complete(): void
    {
        if ($this->closed) {
            return;
        }
        if ($this->uploadId === null) {
            // Smaller than one part: a plain upload.
            $this->client->putObject(['Bucket' => $this->bucket, 'Key' => $this->key, 'Body' => $this->buffer, 'ContentType' => $this->contentType]);
        } else {
            if ($this->buffer !== '') {
                $this->uploadPart($this->buffer);
            }
            $this->client->completeMultipartUpload([
                'Bucket' => $this->bucket, 'Key' => $this->key, 'UploadId' => $this->uploadId,
                'MultipartUpload' => ['Parts' => $this->parts],
            ]);
        }
        $this->buffer = '';
        $this->closed = true;
    }

    /** Throws the upload away (nothing is left behind on S3). */
    public function abort(): void
    {
        if ($this->uploadId !== null && ! $this->closed) {
            try {
                $this->client->abortMultipartUpload(['Bucket' => $this->bucket, 'Key' => $this->key, 'UploadId' => $this->uploadId]);
            } catch (\Throwable) {
                // The bucket's lifecycle rules clean up incomplete uploads anyway.
            }
        }
        $this->buffer = '';
        $this->closed = true;
    }

    private function uploadPart(string $body): void
    {
        $this->uploadId ??= $this->client->createMultipartUpload([
            'Bucket' => $this->bucket, 'Key' => $this->key, 'ContentType' => $this->contentType,
        ])['UploadId'];

        $number = count($this->parts) + 1;
        $result = $this->client->uploadPart([
            'Bucket' => $this->bucket, 'Key' => $this->key, 'UploadId' => $this->uploadId,
            'PartNumber' => $number, 'Body' => $body,
        ]);
        $this->parts[] = ['PartNumber' => $number, 'ETag' => (string) $result['ETag']];
    }

    public function __toString(): string
    {
        return '';
    }

    public function close(): void
    {
        // complete() or abort() finish the upload explicitly.
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return false;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Not seekable');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Not seekable');
    }

    public function isWritable(): bool
    {
        return ! $this->closed;
    }

    public function isReadable(): bool
    {
        return false;
    }

    public function read(int $length): string
    {
        throw new \RuntimeException('Not readable');
    }

    public function getContents(): string
    {
        throw new \RuntimeException('Not readable');
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
