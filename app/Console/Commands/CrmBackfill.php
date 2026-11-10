<?php

namespace App\Console\Commands;

use App\Crm\AccountSnapshot;
use App\Crm\CrmReporter;
use App\Jobs\SendCrmEvent;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Sends account.created for every existing user to the platform CRM. Safe to
 * run again: each event id is derived from the app and user id (the same id
 * registration uses), so the CRM ignores repeats.
 */
class CrmBackfill extends Command
{
    protected $signature = 'crm:backfill {--sync : Send now instead of queueing}';

    protected $description = 'Send every existing account to the platform CRM (idempotent)';

    public function handle(): int
    {
        if (! CrmReporter::enabled()) {
            $this->error('PLATFORM_CRM_TOKEN is not set; nothing sent.');

            return self::FAILURE;
        }

        $count = 0;
        User::query()->chunkById(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                $job = new SendCrmEvent(
                    CrmReporter::createdEventId($user),
                    'account.created',
                    (string) $user->id,
                    ($user->created_at ?? now())->toIso8601ZuluString(),
                    AccountSnapshot::for($user),
                );
                $this->option('sync') ? dispatch_sync($job) : dispatch($job);
                $count++;
            }
        });

        $this->info(($this->option('sync') ? 'Sent' : 'Queued')." account.created for {$count} accounts.");

        return self::SUCCESS;
    }
}
