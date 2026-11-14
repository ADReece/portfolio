<?php

namespace App\Console\Commands;

use App\Models\DownloadArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Deletes expired download ZIPs and their records (and gives up on stuck builds). */
class PruneDownloadArchives extends Command
{
    protected $signature = 'downloads:prune';

    protected $description = 'Delete expired download archives';

    public function handle(): int
    {
        // A build the queue never finished (worker restarted mid-way) counts as failed.
        DownloadArchive::whereIn('status', [DownloadArchive::PENDING, DownloadArchive::BUILDING])
            ->where('updated_at', '<', now()->subHours(2))
            ->update(['status' => DownloadArchive::FAILED, 'error' => 'We couldn\'t prepare this download. Please try again.']);

        $deleted = 0;
        DownloadArchive::where('expires_at', '<', now())->chunkById(200, function ($archives) use (&$deleted) {
            foreach ($archives as $archive) {
                if ($archive->path) {
                    try {
                        Storage::disk('s3')->delete($archive->path);
                    } catch (\Throwable $e) {
                        report($e);

                        continue; // try again next time
                    }
                }
                $archive->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} expired downloads.");

        return self::SUCCESS;
    }
}
