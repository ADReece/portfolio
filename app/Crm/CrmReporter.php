<?php

namespace App\Crm;

use App\Jobs\SendCrmEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Tells the platform CRM about Portfolio's accounts. Every method only queues
 * a job (after the database commit) and does nothing while PLATFORM_CRM_TOKEN
 * is unset.
 */
class CrmReporter
{
    /** Fixed namespace for deterministic event ids (uuid v5). */
    private const NAMESPACE = '6f0c9a3e-2b1d-5c7e-9a40-6c3c7f1e2d85';

    public static function enabled(): bool
    {
        return filled(config('crm.token'));
    }

    /**
     * account.created has the same id whether it comes from registration or
     * crm:backfill, so the CRM counts each account once.
     */
    public static function createdEventId(User|string $user): string
    {
        $id = $user instanceof User ? $user->id : $user;

        return Uuid::uuid5(self::NAMESPACE, config('crm.app').':account.created:'.$id)->toString();
    }

    public function created(User $user): void
    {
        $this->send($user, 'account.created', self::createdEventId($user), self::attributionFor($user));
    }

    /** The optional "attribution" object for account.created (first-touch UTMs and click ids). */
    public static function attributionFor(User $user): ?array
    {
        $a = $user->attribution;

        return $a ? ['attribution' => array_intersect_key($a, array_flip([
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'gclid', 'gbraid', 'wbraid', 'fbclid', 'landing_page', 'referrer_host', 'captured_at',
        ]))] : null;
    }

    public function planChanged(User $user): void
    {
        $this->send($user, 'plan.changed');
    }

    public function cancelled(User $user): void
    {
        $this->send($user, 'subscription.cancelled');
    }

    public function deleted(User $user): void
    {
        // Snapshot now: the account won't exist when the job runs.
        $this->send($user, 'account.deleted');
    }

    /** Debounced: a burst of edits sends one account.updated with the latest details. */
    public function updated(User $user): void
    {
        if (! self::enabled() || $user->is_demo) {
            return;
        }
        $token = (string) Str::uuid();
        Cache::put('crm:update:'.$user->id, $token, now()->addSeconds(config('crm.debounce_seconds') + 3600));

        SendCrmEvent::dispatch((string) Str::uuid(), 'account.updated', (string) $user->id, now()->toIso8601ZuluString(), null, $token)
            ->delay(now()->addSeconds(config('crm.debounce_seconds')));
    }

    private function send(User $user, string $type, ?string $eventId = null, ?array $extra = null): void
    {
        if (! self::enabled() || $user->is_demo) {
            return;
        }

        SendCrmEvent::dispatch(
            $eventId ?? (string) Str::uuid(),
            $type,
            (string) $user->id,
            now()->toIso8601ZuluString(),
            AccountSnapshot::for($user),
            null,
            $extra,
        );
    }
}
