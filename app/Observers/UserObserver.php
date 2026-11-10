<?php

namespace App\Observers;

use App\Crm\CrmReporter;
use App\Models\SubscriptionPlan;
use App\Models\User;

class UserObserver
{
    /** Changes the CRM hears about as plan.changed. */
    private const PLAN_FIELDS = ['subscription_plan_id', 'feature_override', 'feature_override_expires_at'];

    /** Changes the CRM hears about as (debounced) account.updated. */
    private const PROFILE_FIELDS = ['name', 'username', 'email'];

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Automatically assign free plan to new users
        if (!$user->subscription_plan_id) {
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();
            if ($freePlan) {
                $user->subscription_plan_id = $freePlan->id;
                $user->saveQuietly(); // Save without triggering events again
            }
        }

        app(CrmReporter::class)->created($user);
    }

    public function updated(User $user): void
    {
        $reporter = app(CrmReporter::class);

        if ($user->wasChanged(self::PLAN_FIELDS)) {
            $reporter->planChanged($user);
        } elseif ($user->wasChanged(self::PROFILE_FIELDS)) {
            $reporter->updated($user);
        }
    }

    public function deleting(User $user): void
    {
        app(CrmReporter::class)->deleted($user);
    }
}
