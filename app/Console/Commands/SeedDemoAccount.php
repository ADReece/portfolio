<?php

namespace App\Console\Commands;

use App\Demo\DemoAccount;
use Illuminate\Console\Command;

/**
 * Creates or updates the public demo photographer (@demo), safe to re-run:
 *
 *   php artisan portfolio:demo-seed
 *   php artisan portfolio:demo-seed --skip-photos   # account, collections, designs, prints only
 *
 * Photos are free Unsplash stock fetched with UNSPLASH_ACCESS_KEY (each
 * credited to its photographer) and processed like any upload.
 */
class SeedDemoAccount extends Command
{
    protected $signature = 'portfolio:demo-seed {--skip-photos : Don\'t fetch photos from Unsplash}';

    protected $description = 'Create or update the public demo portfolio';

    public function handle(DemoAccount $demo): int
    {
        try {
            $user = $demo->seed(fn (string $line) => $this->line($line), ! $this->option('skip-photos'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Demo ready: '.route('profile.view', $user->username).' (client gallery password: '.config('demo.client_gallery_password').')');

        return self::SUCCESS;
    }
}
