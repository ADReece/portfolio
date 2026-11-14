<?php

namespace App\Jobs;

use App\Downloads\ArchiveWriter;
use App\Models\DownloadArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Builds a big download's ZIP onto storage, streaming each photo from S3
 * into a multipart upload (a few MB of memory, no local disk), and records
 * progress for the page that's waiting. Emails the link if asked.
 */
class BuildDownloadArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Thousands of photos can take a while; one attempt, inside the queue's retry_after.
    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public string $archiveId)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $archive = DownloadArchive::find($this->archiveId);
        if (! $archive || $archive->status !== DownloadArchive::PENDING || $archive->isExpired()) {
            return;
        }

        $archive->update(['status' => DownloadArchive::BUILDING, 'done_count' => 0]);
        $path = 'archives/'.$archive->id.'.zip';
        $lastSaved = 0;

        $written = ArchiveWriter::store($archive, $path, function (int $done) use ($archive, &$lastSaved) {
            // Saved every few photos, so the waiting page shows progress without a write per photo.
            if ($done - $lastSaved >= 5 || $done === $archive->photo_count) {
                DownloadArchive::whereKey($archive->id)->update(['done_count' => $done]);
                $lastSaved = $done;
            }
        });

        if ($written === 0) {
            throw new \RuntimeException('None of the photos could be read from storage.');
        }

        $archive->update([
            'status' => DownloadArchive::READY,
            'path' => $path,
            'done_count' => $archive->photo_count,
            'expires_at' => now()->addHours((int) config('downloads.archive_hours')),
        ]);

        self::emailIfAsked($archive->fresh());
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Building a download archive failed', ['archive_id' => $this->archiveId, 'error' => $e->getMessage()]);
        $archive = DownloadArchive::find($this->archiveId);
        $archive?->update(['status' => DownloadArchive::FAILED, 'error' => 'We couldn\'t prepare this download. Please try again.']);
    }

    /** Sends the link once, if the visitor asked for it and the ZIP is ready. */
    public static function emailIfAsked(DownloadArchive $archive): void
    {
        if (! $archive->email || $archive->emailed_at || ! $archive->isReady()) {
            return;
        }
        $claimed = DownloadArchive::whereKey($archive->id)->whereNull('emailed_at')->update(['emailed_at' => now()]);
        if (! $claimed) {
            return;
        }

        $photographer = $archive->user;
        $from = $photographer ? ($photographer->name ?: $photographer->username) : null;
        Mail::send('emails.download-ready', [
            'archive' => $archive,
            'photographerName' => $from,
            'pageUrl' => $archive->pageUrl(),
            'expiresAt' => $archive->expires_at->format('j F Y, g:i a'),
        ], fn ($message) => $message->to($archive->email)
            ->subject('Your photos are ready to download'.($from ? ' – '.$from : '')));
    }
}
