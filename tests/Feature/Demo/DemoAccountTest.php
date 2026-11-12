<?php

namespace Tests\Feature\Demo;

use App\Jobs\SendCrmEvent;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Template;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Stripe\StripeClient;
use Tests\TestCase;

class DemoAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        config([
            'demo.unsplash_key' => 'test-unsplash-key',
            'demo.categories' => [
                'weddings' => ['query' => 'wedding', 'count' => 4],
                'portraits' => ['query' => 'portrait', 'count' => 2],
                'landscapes' => ['query' => 'landscape', 'count' => 2],
                'events' => ['query' => 'party', 'count' => 1],
            ],
        ]);
        // Stripe must never be called for the demo.
        $never = new class {
            public function __get($name)
            {
                throw new \LogicException('Stripe was called for the demo');
            }
        };
        $this->app->instance(StripeClient::class, $never);
        $this->app->instance('stripe.connect', $never);
    }

    private function fakeUnsplash(): void
    {
        $jpeg = $this->jpeg();
        Http::fake(function (Request $request) use ($jpeg) {
            $url = $request->url();
            if (str_starts_with($url, 'https://api.unsplash.com/search/photos')) {
                parse_str(parse_url($url, PHP_URL_QUERY), $q);
                if (($q['page'] ?? '1') !== '1') {
                    return Http::response(['results' => []]);
                }
                $results = [];
                foreach (range(1, 6) as $i) {
                    $id = $q['query'].'-'.$i;
                    $results[] = [
                        'id' => $id, 'premium' => $i === 1, 'alt_description' => "a {$q['query']} photo",
                        'urls' => ['raw' => "https://images.unsplash.com/photo-{$id}?ixid=x"],
                        'links' => ['html' => "https://unsplash.com/photos/{$id}", 'download_location' => "https://api.unsplash.com/photos/{$id}/download"],
                        'user' => ['name' => "Photographer {$i}", 'links' => ['html' => "https://unsplash.com/@p{$i}"]],
                    ];
                }

                return Http::response(['results' => $results]);
            }
            if (str_contains($url, '/download')) {
                return Http::response(['url' => 'x']);
            }
            if (str_starts_with($url, 'https://images.unsplash.com/')) {
                return Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });
    }

    private function jpeg(): string
    {
        $im = new \Imagick();
        $im->newPseudoImage(1200, 800, 'gradient:#88aacc-#334455');
        $im->setImageFormat('jpeg');

        return $im->getImageBlob();
    }

    private function seeded(): User
    {
        $this->fakeUnsplash();
        ProdigiProduct::create(['sku' => 'GLOBAL-CFPM-16X20', 'name' => 'Framed print', 'category' => 'Framed', 'is_active' => true, 'raw_payload' => ['sku' => 'GLOBAL-CFPM-16X20']])
            ->forceFill(['cost_item' => 10, 'cost_shipping' => 4.5])->save();
        $this->artisan('portfolio:demo-seed')->assertSuccessful();

        return User::where('is_demo', true)->sole();
    }

    public function test_seed_builds_the_demo_through_the_upload_pipeline_with_credits(): void
    {
        $demo = $this->seeded();

        $this->assertSame('demo', $demo->username);
        $this->assertNull($demo->subscriptions()->first(), 'no Stripe subscription');
        $this->assertTrue($demo->hasFeature('selling'));
        $this->assertSame(9, $demo->photos()->count());
        $photo = $demo->photos()->first();
        $this->assertSame(Photo::READY, $photo->status, 'processed like an upload');
        Storage::disk('s3')->assertExists(str_replace('photos/', 'thumbnails/', $photo->url));
        Storage::disk('s3')->assertExists(str_replace('photos/', 'watermarked/', $photo->url));
        $this->assertStringStartsWith('Photographer ', $photo->credit['name']);
        $this->assertStringContainsString('utm_source=portfolio', $photo->credit['profile_url']);
        $this->assertStringContainsString('on Unsplash', $photo->caption);
        $this->assertSame(0, Photo::where('credit->source_id', 'wedding-1')->count(), 'Unsplash+ photos are skipped');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/download') && $r->hasHeader('Authorization', 'Client-ID test-unsplash-key'));

        $client = Collection::where('user_id', $demo->id)->where('private', true)->sole();
        $this->assertTrue(password_verify('demo', $client->password));
        $this->assertGreaterThan(0, $client->photos()->count());
        $this->assertTrue(Collection::where('user_id', $demo->id)->where('name', 'Weddings')->sole()->digital_sales);
        $this->assertSame(1, Template::where('user_id', $demo->id)->count());
        $this->assertSame(1, $demo->enabledProdigiProducts()->count());
        $this->assertNotNull($demo->logo_path);
    }

    public function test_reseeding_updates_in_place(): void
    {
        $demo = $this->seeded();
        $this->artisan('portfolio:demo-seed')->assertSuccessful();

        $this->assertSame(1, User::where('is_demo', true)->count());
        $this->assertSame(9, $demo->photos()->count());
        $this->assertSame(5, Collection::where('user_id', $demo->id)->count());
        $this->assertSame(1, Template::where('user_id', $demo->id)->count());
    }

    public function test_seed_needs_an_unsplash_key_unless_photos_are_skipped(): void
    {
        config(['demo.unsplash_key' => null]);
        $this->artisan('portfolio:demo-seed')->expectsOutputToContain('UNSPLASH_ACCESS_KEY')->assertFailed();
        $this->artisan('portfolio:demo-seed', ['--skip-photos' => true])->assertSuccessful();
        $this->assertTrue(User::where('is_demo', true)->exists());
    }

    public function test_the_demo_cannot_log_in_or_reset_its_password(): void
    {
        $demo = $this->seeded();
        $demo->forceFill(['password' => bcrypt('known-password')])->save();
        Notification::fake();

        $this->post('/login', ['email' => $demo->email, 'password' => 'known-password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/forgot-password', ['email' => $demo->email])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_demo_checkouts_create_no_orders_and_never_call_stripe(): void
    {
        $demo = $this->seeded();
        $photo = Collection::where('user_id', $demo->id)->where('name', 'Weddings')->sole()->photos()->first();
        $product = $demo->enabledProdigiProducts()->first();

        $this->get(route('print-purchase.show', $photo))->assertOk()->assertSee('This is a demo portfolio');
        $this->post(route('print-purchase.store', $photo), ['prodigi_product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect()->assertSessionHas('demo');

        $weddings = Collection::where('user_id', $demo->id)->where('name', 'Weddings')->sole();
        $this->get(route('download-purchase.show', $weddings))->assertOk()->assertSee('This is a demo portfolio');
        $this->post(route('download-purchase.store', $weddings), ['photo_ids' => [$photo->id], 'customer_email' => 'a@example.com'])
            ->assertRedirect()->assertSessionHas('demo');

        $this->assertSame(0, Order::count());
    }

    public function test_visitor_requests_send_no_emails(): void
    {
        $demo = $this->seeded();
        config(['mail.default' => 'array']);
        $portrait = Collection::where('user_id', $demo->id)->where('name', 'Portraits')->sole()->photos()->first();
        $client = Collection::where('user_id', $demo->id)->where('private', true)->sole();

        $this->postJson(route('photos.request-download', $portrait), ['email' => 'a@example.com'])->assertStatus(422);
        $this->postJson(route('photos.request-purchase', $portrait), ['email' => 'a@example.com'])->assertStatus(422);
        $this->withSession(['unlocked_collections' => [(string) $client->id]])
            ->postJson(route('collections.request-archive', $client->id), ['email' => 'a@example.com'])->assertStatus(422);

        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());
    }

    public function test_demo_is_left_out_of_crm_and_admin_stats(): void
    {
        config(['crm.token' => 'crm-token']);
        Queue::fake();
        $demo = $this->seeded();
        $demo->update(['bio' => 'changed']);

        Queue::assertNotPushed(SendCrmEvent::class);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertViewHas('totalUsers', 1);
    }

    public function test_public_pages_show_the_demo_panel_and_homepage_links_to_it(): void
    {
        $demo = $this->seeded();
        cache()->forget('demo:username');

        $this->get('/')->assertOk()->assertSee('See a live demo')->assertSee(route('profile.view', 'demo'));
        $this->get(route('profile.view', 'demo'))->assertOk()
            ->assertSee('This is a live demo portfolio')
            ->assertSee('Weddings')
            ->assertDontSee('Harper &amp; Jordan: client gallery</h', false);
        $this->get(route('profile.photo-credits', 'demo'))->assertOk()->assertSee('Photographer 2');
    }
}
