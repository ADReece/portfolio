<?php

namespace App\Downloads;

use App\Models\DownloadArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

/**
 * Writes a ZIP of photos read from storage, one photo at a time, without
 * holding a photo or the ZIP in memory or on disk:
 *  - stream(): straight to the browser (instant downloads);
 *  - store(): onto the s3 disk with a multipart upload (background downloads).
 */
class ArchiveWriter
{
    /**
     * @param  list<array{key: string, name: string, size: int}>  $files
     * @param  resource|\Psr\Http\Message\StreamInterface|null  $output  null = the HTTP response
     * @param  (callable(int $done): void)|null  $progress
     * @return int photos written
     */
    public static function write(array $files, $output = null, ?callable $progress = null): int
    {
        $zip = new ZipStream(
            outputStream: $output,
            sendHttpHeaders: false,
            defaultCompressionMethod: CompressionMethod::STORE, // photos are already compressed
            defaultEnableZeroHeader: true, // sizes go after each file, so nothing is read twice
            flushOutput: $output === null,
        );
        $disk = Storage::disk('s3');
        $written = 0;

        foreach ($files as $i => $file) {
            try {
                $stream = $disk->readStream($file['key']);
            } catch (\Throwable) {
                $stream = null; // storage errors throw on S3
            }
            if (! $stream) {
                Log::warning('Photo missing from storage for a download', ['key' => $file['key']]);
            } else {
                $zip->addFileFromStream($file['name'], $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $written++;
            }
            if ($progress) {
                $progress($i + 1);
            }
        }
        $zip->finish();

        return $written;
    }

    /** Builds the archive onto the s3 disk at $path. */
    public static function store(DownloadArchive $archive, string $path, ?callable $progress = null): int
    {
        $disk = Storage::disk('s3');

        if ($disk instanceof \Illuminate\Filesystem\AwsS3V3Adapter) {
            $target = new S3MultipartStream($disk->getClient(), (string) config('filesystems.disks.s3.bucket'), $disk->path($path), 'application/zip');
            try {
                $written = self::write($archive->files, $target, $progress);
                $target->complete();
            } catch (\Throwable $e) {
                $target->abort();

                throw $e;
            }

            return $written;
        }

        // Other drivers (local development, tests): spool through a temporary stream.
        $temp = fopen('php://temp/maxmemory:'.(8 * 1024 * 1024), 'w+b');
        $written = self::write($archive->files, $temp, $progress);
        rewind($temp);
        $disk->writeStream($path, $temp);
        fclose($temp);

        return $written;
    }
}
