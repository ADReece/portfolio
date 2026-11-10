<?php

namespace Tests\Feature\Crm;

use App\Crm\CrmReporter;
use App\Jobs\SendCrmEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrmReportingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://crm.test/api/platform/v1/events';

    protected function setUp(): void
    {
        parent::setUp();
        config(['crm.url' => 'https://crm.test', 'crm.token' => 'crm-secret', 'crm.debounce_seconds' => 120]);
        $this->seed(SubscriptionPlanSeeder::class);
        Http::preventStrayRequests();
        Http::fake([self::URL => Http::response(['accepted' => true], 202)]);
    }

    /** @return list<array> the event bodies sent to the CRM */
    private function sent(): array
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0]->data())->values()->all();
    }

    private function plan(string $slug): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $slug)->firstOrFail();
    }

    private function subscribe(User $user, string $plan, string $interval = 'month', array $attrs = []): void
    {
        $p = $this->plan($plan);
        $p->update(['stripe_price_id' => 'price_'.$plan.'_m', 'annual_stripe_price_id' => 'price_'.$plan.'_y']);
        $user->update(['subscription_plan_id' => $p->id]);
        $user->subscriptions()->create($attrs + [
            'type' => 'default', 'stripe_id' => 'sub_'.uniqid(), 'stripe_status' => 'active',
            'stripe_price' => $interval === 'year' ? 'price_'.$plan.'_y' : 'price_'.$plan.'_m', 'quantity' => 1,
        ]);
    }

    public function test_registration_sends_account_created_with_the_contract_fields(): void
    {
        $this->post('/register', [
            'name' => 'Steph Shoots', 'username' => 'stephshoots', 'email' => 'steph@example.com',
            'password' => 'Sup3r-secret-pass!', 'password_confirmation' => 'Sup3r-secret-pass!', 'terms' => '1',
        ]);
        $user = User::where('email', 'steph@example.com')->sole();

        Http::assertSent(function (Request $r) use ($user) {
            return $r->url() === self::URL
                && $r->hasHeader('Authorization', 'Bearer crm-secret')
                && $r->hasHeader('Idempotency-Key', CrmReporter::createdEventId($user))
                && $r['event_id'] === CrmReporter::createdEventId($user)
                && $r['app'] === 'portfolio'
                && $r['type'] === 'account.created'
                && str_ends_with($r['occurred_at'], 'Z')
                && $r['account']['external_id'] === (string) $user->id
                && $r['account']['email'] === 'steph@example.com'
                && $r['account']['name'] === 'Steph Shoots'
                && $r['account']['plan'] === 'free' && $r['account']['status'] === 'free' && $r['account']['interval'] === null
                && $r['account']['mrr_pence'] === 0
                && str_ends_with($r['account']['url'], '/@stephshoots');
        });
        // Never a password, token or payment detail.
        $body = json_encode($this->sent());
        $this->assertStringNotContainsString('Sup3r-secret-pass', $body);
        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('remember_token', $body);
    }

    public function test_plan_change_reports_status_interval_and_mrr(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'photographer');

        $last = collect($this->sent())->last();
        $this->assertSame('plan.changed', $last['type']);
        $this->assertSame(['plan' => 'photographer', 'interval' => 'month', 'status' => 'active', 'mrr_pence' => 399],
            array_intersect_key($last['account'], array_flip(['plan', 'interval', 'status', 'mrr_pence'])));

        $yearly = User::factory()->create();
        $this->subscribe($yearly, 'videographer', 'year');
        $this->assertSame(['interval' => 'year', 'mrr_pence' => 666], array_intersect_key(collect($this->sent())->last()['account'], array_flip(['interval', 'mrr_pence']))); // 79.90 / 12
    }

    public function test_feature_override_reports_zero_mrr(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'photographer');
        $user->update(['feature_override' => true]);

        $last = collect($this->sent())->last();
        $this->assertSame('plan.changed', $last['type']);
        $this->assertSame(0, $last['account']['mrr_pence']);
    }

    public function test_cancellation_and_past_due(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'photographer');
        $sub = $user->subscriptions()->sole();

        $sub->update(['stripe_status' => 'past_due']);
        $this->assertSame('past_due', collect($this->sent())->last()['account']['status']);

        $sub->update(['stripe_status' => 'active', 'ends_at' => now()->addDays(20)]);
        $last = collect($this->sent())->last();
        $this->assertSame('subscription.cancelled', $last['type']);
        $this->assertSame('cancelled', $last['account']['status']);
        $this->assertSame(0, $last['account']['mrr_pence']);
    }

    public function test_deletion_sends_the_account_as_it_was(): void
    {
        $user = User::factory()->create(['email' => 'gone@example.com']);
        $id = (string) $user->id;
        $user->delete();

        $last = collect($this->sent())->last();
        $this->assertSame('account.deleted', $last['type']);
        $this->assertSame($id, $last['account']['external_id']);
        $this->assertSame('gone@example.com', $last['account']['email']);
    }

    public function test_profile_updates_are_debounced_to_one_event_with_the_latest_details(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $user->update(['name' => 'First']);
        $user->update(['name' => 'Second']);

        $jobs = Queue::pushed(SendCrmEvent::class, fn ($job) => $job->type === 'account.updated');
        $this->assertCount(2, $jobs);
        $this->assertNotNull($jobs->first()->delay);

        Queue::fake()->except([]); // run the queued jobs by hand
        foreach ($jobs as $job) {
            $job->handle();
        }
        $updates = collect($this->sent())->where('type', 'account.updated')->values();
        $this->assertCount(1, $updates, 'the superseded update is skipped');
        $this->assertSame('Second', $updates[0]['account']['name']);
    }

    public function test_jobs_are_queued_after_commit_with_backoff(): void
    {
        Queue::fake();
        User::factory()->create();

        Queue::assertPushed(SendCrmEvent::class, fn ($job) => $job->afterCommit === true
            && $job->backoff === [60, 300, 1800, 7200, 43200] && $job->tries === 6);
    }

    public function test_crm_errors_retry_except_rejected_payloads(): void
    {
        config(['crm.url' => 'https://crm-errors.test']);
        Http::fake([
            'https://crm-errors.test/*' => Http::sequence()
                ->push(['errors' => ['account.email' => ['required']]], 422)
                ->push([], 503),
        ]);

        // 422: the payload is wrong, so the job fails instead of retrying.
        $job = (new SendCrmEvent('e1', 'account.created', 'u1', now()->toIso8601ZuluString(), ['external_id' => 'u1']))->withFakeQueueInteractions();
        $job->handle();
        $job->assertFailed();

        // 5xx (or 401): thrown, so the queue retries with backoff.
        $this->expectException(\RuntimeException::class);
        (new SendCrmEvent('e2', 'account.created', 'u1', now()->toIso8601ZuluString(), ['external_id' => 'u1']))->handle();
    }

    public function test_off_when_the_token_is_unset(): void
    {
        config(['crm.token' => null]);
        Queue::fake();

        $user = User::factory()->create();
        $user->update(['name' => 'Changed']);
        $user->delete();

        Queue::assertNothingPushed();
        $this->artisan('crm:backfill')->assertFailed();
    }

    public function test_backfill_is_idempotent_with_deterministic_ids(): void
    {
        $users = User::factory()->count(3)->create();
        Http::fake([self::URL => Http::response([], 202)]); // reset the recorded requests

        $this->artisan('crm:backfill --sync')->expectsOutputToContain('Sent account.created for 3 accounts')->assertSuccessful();
        $this->artisan('crm:backfill --sync')->assertSuccessful();

        $ids = collect($this->sent())->where('type', 'account.created')->pluck('event_id');
        $this->assertCount(6, $ids);
        $this->assertCount(3, $ids->unique(), 'a second run reuses the same event ids');
        $this->assertEqualsCanonicalizing($users->map(fn ($u) => CrmReporter::createdEventId($u))->all(), $ids->unique()->values()->all());
    }

    public function test_url_is_the_live_custom_domain(): void
    {
        $user = User::factory()->create(['username' => 'stephshoots']);
        $this->subscribe($user, 'photographer');
        \App\Models\CustomDomain::factory()->verified()->create(['user_id' => $user->id, 'hostname' => 'stephshoots.com']);

        $this->assertSame('https://stephshoots.com', \App\Crm\AccountSnapshot::for($user->fresh())['url']);
    }
}
