<?php

namespace App\Jobs;

use App\Crm\AccountSnapshot;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one event to the platform CRM (POST /api/platform/v1/events). Retries
 * reuse the same event_id (also the Idempotency-Key), so the CRM never counts
 * an event twice. Dispatched after the database commit, never blocking a request.
 */
class SendCrmEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** 1m, 5m, 30m, 2h, 12h */
    public array $backoff = [60, 300, 1800, 7200, 43200];

    /**
     * @param  array|null  $account  snapshot taken when the event happened; null
     *                               means "take it when sending" (debounced updates)
     */
    public function __construct(
        public string $eventId,
        public string $type,
        public string $userId,
        public string $occurredAt,
        public ?array $account = null,
        public ?string $debounceToken = null,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $token = (string) config('crm.token');
        if ($token === '') {
            return; // integration switched off
        }

        $account = $this->account;
        if ($account === null) {
            // A later edit superseded this debounced update; that one will send.
            if ($this->debounceToken && Cache::get('crm:update:'.$this->userId) !== $this->debounceToken) {
                return;
            }
            $user = User::find($this->userId);
            if (! $user) {
                return; // deleted since; account.deleted covers it
            }
            $account = AccountSnapshot::for($user);
        }

        $response = Http::withToken($token)
            ->withHeaders(['Idempotency-Key' => $this->eventId])
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post(config('crm.url').'/api/platform/v1/events', [
                'event_id' => $this->eventId,
                'app' => config('crm.app'),
                'type' => $this->type,
                'occurred_at' => $this->occurredAt,
                'account' => $account,
            ]);

        if ($response->successful()) {
            return;
        }
        if ($response->status() === 422) {
            // The CRM rejected the payload; retrying the same body won't help.
            Log::error('CRM rejected event', ['event_id' => $this->eventId, 'type' => $this->type, 'errors' => $response->json('errors')]);
            $this->fail(new \RuntimeException('CRM rejected event '.$this->eventId.' (422)'));

            return;
        }

        // 401 (token not set up yet on the CRM side), 5xx, timeouts: retry with backoff.
        throw new \RuntimeException('CRM returned HTTP '.$response->status().' for event '.$this->eventId);
    }
}
