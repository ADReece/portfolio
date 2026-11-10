<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Makes an account an admin when it logs in with an email listed in
 * ADMIN_EMAILS (config app.admin_emails). It never removes admin rights.
 */
class GrantConfiguredAdmin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user || $user->is_admin) {
            return;
        }

        if (in_array(strtolower((string) $user->email), config('app.admin_emails', []), true)) {
            $user->forceFill(['is_admin' => true])->save();
            Log::info('Granted admin from ADMIN_EMAILS', ['user_id' => $user->getKey()]);
        }
    }
}
