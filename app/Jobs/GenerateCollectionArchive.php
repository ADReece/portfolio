<?php

namespace App\Jobs;

use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GenerateCollectionArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // A large collection read back from S3 can take minutes: one attempt,
    // well within the queue's retry_after (config/queue.php).
    public int $timeout = 900;
    public int $tries = 1;

    protected $collectionId;
    protected $email;

    /**
     * Create a new job instance.
     */
    public function __construct($collectionId, $email)
    {
        $this->collectionId = $collectionId;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $collection = Collection::findOrFail($this->collectionId);

        // Every ready photo in the collection (not ones still processing or failed).
        $photos = $collection->photos()->ready()->with('set.collection')->orderBy('photos.created_at')->get();

        if ($photos->isEmpty()) {
            // Send email saying no photos found
            Mail::send('emails.archive-empty', [
                'collectionName' => $collection->name,
            ], function ($message) use ($collection) {
                $message->to($this->email)
                    ->subject('No Photos Available - ' . $collection->name);
            });
            return;
        }

        // Create a unique filename for the archive
        $filename = 'collection-' . $collection->id . '-' . time() . '.zip';
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        // Build the ZIP on disk, reading each photo straight from storage (S3
        // or the local disk), then stream it back up: memory stays flat
        // whatever the collection's size.
        $zip = new ZipArchive;
        $files = [];
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $disk = Storage::disk('s3');
            foreach ($photos->values() as $index => $photo) {
                try {
                    // Watermarked collections give clients the watermarked copies.
                    $key = \App\Support\GalleryAccess::downloadKey($photo);
                    $in = $disk->readStream($key);
                    $local = tempnam(sys_get_temp_dir(), 'arc-');
                    $out = fopen($local, 'wb');
                    stream_copy_to_stream($in, $out);
                    fclose($out);
                    if (is_resource($in)) {
                        fclose($in);
                    }
                    $files[] = $local;

                    $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION)) ?: 'jpg';
                    $name = 'photo-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'.'.$extension;
                    $zip->addFile($local, $name);
                    $zip->setCompressionName($name, ZipArchive::CM_STORE); // photos are already compressed
                } catch (\Throwable $e) {
                    Log::error('Failed to add photo to archive: '.$e->getMessage(), [
                        'photo_id' => $photo->id,
                        'collection_id' => $collection->id,
                    ]);
                }
            }

            $zip->close();
            foreach ($files as $local) {
                @unlink($local);
            }

            $stream = fopen($tempPath, 'rb');
            Storage::disk('s3')->writeStream('archives/'.$filename, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            unlink($tempPath);

            // Generate download page URL (valid for 48 hours)
            $downloadPageUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('collections.archive-download-page', now()->addHours(48), [
                'collection' => $collection->id,
                'filename' => $filename
            ]);

            // Queue cleanup job for 48 hours from now
            \App\Jobs\CleanupExpiredArchive::dispatch($filename);

            // Send email with download page link
            Mail::send('emails.archive-ready', [
                'collectionName' => $collection->name,
                'downloadPageUrl' => $downloadPageUrl,
                'photoCount' => $photos->count(),
                'expiresAt' => now()->addHours(48)->format('F j, Y g:i A'),
            ], function ($message) use ($collection) {
                $message->to($this->email)
                    ->subject('Your Download is Ready - ' . $collection->name);
            });

        } else {
            // Failed to create ZIP
            Log::error('Failed to create ZIP archive', [
                'collection_id' => $collection->id,
                'temp_path' => $tempPath
            ]);

            // Clean up temp file if it exists
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Send error email
            Mail::send('emails.archive-failed', [
                'collectionName' => $collection->name,
            ], function ($message) use ($collection) {
                $message->to($this->email)
                    ->subject('Download Failed - ' . $collection->name);
            });
        }
    }
}

