<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Videos\MultipartUploads;
use Illuminate\Console\Command;

/** Aborts uploads left unfinished for a day (a closed tab), so their parts don't sit in the bucket. */
class CleanVideoUploads extends Command
{
    protected $signature = 'videos:clean-uploads';

    protected $description = 'Abort video uploads left unfinished for a day';

    public function handle(MultipartUploads $uploads): int
    {
        $count = 0;
        Video::query()->where('status', Video::UPLOADING)->where('created_at', '<', now()->subDay())->each(function (Video $video) use ($uploads, &$count) {
            if ($video->upload_id) {
                try {
                    $uploads->abort($video->key, $video->upload_id);
                } catch (\Throwable) {
                }
            }
            $video->delete();
            $count++;
        });
        $this->info("Cleared {$count} unfinished upload(s).");

        return self::SUCCESS;
    }
}
