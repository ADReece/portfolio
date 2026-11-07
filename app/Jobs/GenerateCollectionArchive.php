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

        // Get all photos from all sets in the collection
        $photos = $collection->sets()
            ->with('photos')
            ->get()
            ->pluck('photos')
            ->flatten();

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

        // Create the ZIP archive in temporary directory
        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($photos as $index => $photo) {
                try {
                    // Get the full resolution image URL
                    $imageUrl = $photo->getUri();

                    // Download the image content
                    $imageContent = file_get_contents($imageUrl);

                    if ($imageContent !== false) {
                        // Use original filename or create one
                        $photoFilename = $photo->original_filename ??
                                       'photo-' . ($index + 1) . '.' . ($photo->extension ?? 'jpg');

                        // Add to ZIP
                        $zip->addFromString($photoFilename, $imageContent);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to add photo to archive: ' . $e->getMessage(), [
                        'photo_id' => $photo->id,
                        'collection_id' => $collection->id
                    ]);
                    // Continue with other photos
                }
            }

            $zip->close();

            // Upload to S3
            Storage::disk('s3')->put(
                'archives/' . $filename,
                file_get_contents($tempPath),
                'private' // Private visibility
            );

            // Delete temporary file
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

