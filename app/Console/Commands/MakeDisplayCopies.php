<?php

namespace App\Console\Commands;

use App\Jobs\MakeDisplayCopy;
use App\Models\Photo;
use Illuminate\Console\Command;

/**
 * Queues web-sized copies for photos uploaded before they existed. Pages use
 * the original until a photo's copy is made, so this can run at any pace.
 */
class MakeDisplayCopies extends Command
{
    protected $signature = 'photos:make-display-copies {--limit=0 : Queue at most this many (0 = all)} {--now : Make them here instead of queueing}';

    // The scheduler runs it hourly; a batch already waiting isn't queued twice.

    protected $description = 'Make web-sized copies of existing photos';

    public function handle(): int
    {
        if (! $this->option('now') && \Illuminate\Support\Facades\Queue::size('background') > 0) {
            $this->info('Copies are still being made from the last run.');

            return self::SUCCESS;
        }
        // Only photos stored under photos/ (their copies have their own keys).
        $query = Photo::query()->where('status', Photo::READY)->where('has_display', false)->where('url', 'like', 'photos/%')->orderBy('created_at', 'desc');
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $count = 0;
        foreach ($query->pluck('id') as $id) {
            $this->option('now') ? MakeDisplayCopy::dispatchSync($id) : MakeDisplayCopy::dispatch($id);
            $count++;
        }
        $this->info(($this->option('now') ? 'Made' : 'Queued')." {$count} web-sized ".($count === 1 ? 'copy' : 'copies').'.');

        return self::SUCCESS;
    }
}
