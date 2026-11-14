<?php

namespace Tests\Feature\Downloads;

use App\Models\Collection;
use App\Models\DownloadArchive;
use App\Models\Photo;
use App\Models\Set;
use App\Models\Setting;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Downloading several photos as one ZIP: selections, sets, collections and favourites. */
class BulkDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Collection $collection;

    private Set $ceremony;

    private Set $party;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        config(['mail.default' => 'array']);
        Setting::updateOrCreate(['key' => 'default_currency'], ['value' => 'GBP']);
        Currency::flush();

        $this->owner = User::factory()->create(['is_admin' => true, 'name' => 'Ava Lens']);
        $this->collection = Collection::factory()->published()->create(['user_id' => $this->owner->id, 'name' => 'Smith Wedding', 'private' => true]);
        $this->ceremony = Set::create(['collection_id' => $this->collection->id, 'name' => 'Ceremony', 'hide_from_portfolio' => false]);
        $this->party = Set::create(['collection_id' => $this->collection->id, 'name' => 'Party', 'hide_from_portfolio' => false]);
    }

    private function photo(Set $set, string $file, array $attributes = []): Photo
    {
        $key = 'photos/'.$this->owner->id.'/'.uniqid().'.jpg';
        Storage::disk('s3')->put($key, 'original:'.$file);
        Storage::disk('s3')->put(str_replace('photos/', 'watermarked/', $key), 'watermarked:'.$file);

        return Photo::create($attributes + [
            'user_id' => $this->owner->id, 'set_id' => $set->id, 'url' => $key,
            'original_filename' => $file, 'private' => false, 'size' => 1000,
        ]);
    }

    private function unlocked(): self
    {
        return $this->withSession(['unlocked_collections' => [(string) $this->collection->id]]);
    }

    private function prepare(array $payload): TestResponse
    {
        return $this->postJson(route('bulk-downloads.prepare'), $payload);
    }

    /** @return array<string, string> name => contents */
    private function zipEntries(string $zip): array
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($path, $zip);
        $archive = new \ZipArchive;
        $this->assertTrue($archive->open($path) === true, 'a valid ZIP');
        $entries = [];
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $entries[$archive->getNameIndex($i)] = $archive->getFromIndex($i);
        }
        $archive->close();
        unlink($path);

        return $entries;
    }

    private function instantZip(TestResponse $prepared): array
    {
        $prepared->assertOk()->assertJsonPath('mode', 'instant');
        $response = $this->get($prepared->json('url'));
        $response->assertOk()->assertHeader('Content-Type', 'application/zip');

        return [$response, $this->zipEntries($response->streamedContent())];
    }

    public function test_a_small_selection_streams_straight_away_with_original_names_deduplicated(): void
    {
        $a = $this->photo($this->ceremony, 'DSC_0001.JPG');
        $b = $this->photo($this->ceremony, 'DSC_0001.JPG'); // same camera name twice
        $c = $this->photo($this->party, 'DSC_0002.jpg');
        $this->photo($this->party, 'DSC_0003.jpg'); // not chosen

        [$response, $entries] = $this->instantZip($this->unlocked()->prepare([
            'collection_id' => $this->collection->id, 'photo_ids' => [$a->id, $b->id, $c->id],
        ]));

        $this->assertStringContainsString('Smith Wedding.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(['DSC_0001.jpg', 'DSC_0001 (2).jpg', 'DSC_0002.jpg'], array_keys($entries));
        $this->assertSame('original:DSC_0001.JPG', $entries['DSC_0001.jpg']);
        $this->assertSame(0, \App\Models\Order::count());
    }

    public function test_a_set_downloads_on_its_own_named_after_the_set(): void
    {
        $this->photo($this->ceremony, 'a.jpg');
        $this->photo($this->party, 'b.jpg');

        [$response, $entries] = $this->instantZip($this->unlocked()->prepare(['set_id' => $this->party->id]));

        $this->assertStringContainsString('Party.zip', $response->headers->get('Content-Disposition'));
        $this->assertSame(['b.jpg'], array_keys($entries));
    }

    public function test_private_galleries_must_be_unlocked_and_links_are_signed(): void
    {
        $photo = $this->photo($this->ceremony, 'a.jpg');

        $this->prepare(['collection_id' => $this->collection->id])->assertNotFound();
        $this->prepare(['photo_ids' => [$photo->id]])->assertUnprocessable(); // nothing they can see

        $url = $this->unlocked()->prepare(['collection_id' => $this->collection->id])->json('url');
        $this->get($url.'x')->assertForbidden(); // tampered signature
        $this->travel(16)->minutes();
        $this->get($url)->assertForbidden(); // expired
    }

    public function test_public_galleries_leave_out_hidden_photos_and_sets(): void
    {
        $this->collection->update(['private' => false]);
        $this->party->update(['hide_from_portfolio' => true]);
        $this->photo($this->ceremony, 'shown.jpg');
        $this->photo($this->ceremony, 'hidden.jpg', ['hide_from_portfolio' => true]);
        $this->photo($this->ceremony, 'private.jpg', ['private' => true]);
        $this->photo($this->party, 'in-hidden-set.jpg');

        [, $entries] = $this->instantZip($this->prepare(['collection_id' => $this->collection->id]));
        $this->assertSame(['shown.jpg'], array_keys($entries));

        $this->collection->update(['status' => 'Draft']);
        $this->prepare(['collection_id' => $this->collection->id])->assertNotFound();
    }

    public function test_watermarked_collections_hand_out_watermarked_copies_but_the_photographer_gets_originals(): void
    {
        $this->collection->update(['watermarked' => true]);
        $this->photo($this->ceremony, 'a.jpg');

        [, $client] = $this->instantZip($this->unlocked()->prepare(['collection_id' => $this->collection->id]));
        $this->assertSame('watermarked:a.jpg', $client['a.jpg']);

        [, $owner] = $this->instantZip($this->actingAs($this->owner)->prepare(['set_id' => $this->ceremony->id]));
        $this->assertSame('original:a.jpg', $owner['a.jpg']);
    }

    public function test_photos_for_sale_are_bought_not_downloaded(): void
    {
        \Tests\Support\FakeConnectStripe::install($this->app);
        $this->owner->forceFill(['stripe_connect_id' => 'acct_1', 'stripe_connect_enabled' => true, 'digital_photo_price' => 5])->save();
        $this->collection->update(['digital_sales' => true]);
        $photo = $this->photo($this->ceremony, 'a.jpg');

        $this->unlocked()->prepare(['collection_id' => $this->collection->id])
            ->assertForbidden()->assertJsonPath('buy_url', route('download-purchase.show', $this->collection));
        $this->unlocked()->prepare(['photo_ids' => [$photo->id]])->assertForbidden();

        // The photographer can still download their own.
        $this->actingAs($this->owner)->prepare(['collection_id' => $this->collection->id])->assertOk();
    }

    public function test_big_downloads_are_built_in_the_background_with_progress_and_an_optional_email(): void
    {
        config(['downloads.instant_max_photos' => 2]);
        foreach (['a', 'b', 'c'] as $name) {
            $this->photo($this->ceremony, $name.'.jpg');
        }

        $prepared = $this->unlocked()->prepare(['collection_id' => $this->collection->id]);
        $prepared->assertStatus(202)->assertJsonPath('mode', 'background')->assertJsonPath('count', 3);

        // The queue (sync in tests) has built it: the page offers the file.
        $state = $this->getJson($prepared->json('status_url'))->assertOk();
        $state->assertJsonPath('status', 'ready')->assertJsonPath('done', 3)->assertJsonPath('total', 3);
        $archive = DownloadArchive::sole();
        $this->assertSame(['a.jpg', 'b.jpg', 'c.jpg'], array_keys($this->zipEntries(Storage::disk('s3')->get($archive->path))));
        $this->get($prepared->json('status_url'))->assertOk()->assertSee('Download Smith Wedding.zip');
        $this->get($state->json('download_url'))->assertRedirect();

        // "Email me the link too", once.
        $this->postJson($prepared->json('email_url'), ['email' => 'client@example.com'])->assertOk()->assertJsonPath('message', 'We\'ve emailed the link to client@example.com.');
        $this->postJson($prepared->json('email_url'), ['email' => 'client@example.com'])->assertUnprocessable();
        $sent = Mail::getSymfonyTransport()->messages();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('/downloads/archives/'.$archive->id, $sent->first()->getOriginalMessage()->getHtmlBody());
    }

    public function test_email_asked_for_up_front_is_sent_when_the_build_finishes(): void
    {
        config(['downloads.instant_max_photos' => 1]);
        $this->photo($this->ceremony, 'a.jpg');
        $this->photo($this->ceremony, 'b.jpg');

        $this->unlocked()->prepare(['collection_id' => $this->collection->id, 'email' => 'client@example.com'])->assertStatus(202);

        $this->assertCount(1, Mail::getSymfonyTransport()->messages());
        $this->assertNotNull(DownloadArchive::sole()->emailed_at);
    }

    public function test_visitors_can_only_build_a_few_big_downloads_at_once_and_requests_are_rate_limited(): void
    {
        config(['downloads.instant_max_photos' => 0, 'downloads.max_building_per_ip' => 1]);
        $this->photo($this->ceremony, 'a.jpg');
        DownloadArchive::create(['mode' => 'background', 'name' => 'x.zip', 'files' => [], 'photo_count' => 1, 'status' => 'building', 'requester_ip' => '127.0.0.1', 'expires_at' => now()->addDay()]);

        $this->unlocked()->prepare(['collection_id' => $this->collection->id])->assertStatus(429);

        config(['downloads.instant_max_photos' => 25]);
        for ($i = 0; $i < 9; $i++) { // 10 a minute, counting the one above
            $this->unlocked()->prepare(['collection_id' => $this->collection->id])->assertOk();
        }
        $this->unlocked()->prepare(['collection_id' => $this->collection->id])->assertStatus(429);
    }

    public function test_the_public_portfolio_selection_spans_collections_and_skips_what_is_not_public(): void
    {
        $this->collection->update(['private' => false]);
        $other = Collection::factory()->published()->create(['user_id' => $this->owner->id, 'name' => 'Landscapes']);
        $otherSet = Set::create(['collection_id' => $other->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
        $a = $this->photo($this->ceremony, 'a.jpg');
        $b = $this->photo($otherSet, 'b.jpg');
        $private = Collection::factory()->published()->create(['user_id' => $this->owner->id, 'private' => true]);
        $c = $this->photo(Set::create(['collection_id' => $private->id, 'name' => 'S', 'hide_from_portfolio' => false]), 'c.jpg');

        [$response, $entries] = $this->instantZip($this->prepare(['photo_ids' => [$a->id, $b->id, $c->id]]));

        $this->assertSame(['a.jpg', 'b.jpg'], array_keys($entries));
        $this->assertStringContainsString('Ava Lens photos.zip', $response->headers->get('Content-Disposition'));
    }

    public function test_expired_archives_are_pruned_from_storage(): void
    {
        Storage::disk('s3')->put('archives/old.zip', 'zip');
        DownloadArchive::create(['mode' => 'background', 'name' => 'x.zip', 'files' => [], 'photo_count' => 1, 'status' => 'ready', 'path' => 'archives/old.zip', 'expires_at' => now()->subMinute()]);
        $live = DownloadArchive::create(['mode' => 'background', 'name' => 'y.zip', 'files' => [], 'photo_count' => 1, 'status' => 'ready', 'expires_at' => now()->addDay()]);

        $this->artisan('downloads:prune')->assertSuccessful();

        Storage::disk('s3')->assertMissing('archives/old.zip');
        $this->assertSame([$live->id], DownloadArchive::pluck('id')->all());
    }

    public function test_gallery_pages_show_selection_and_download_buttons(): void
    {
        $this->photo($this->ceremony, 'a.jpg');
        $this->photo($this->party, 'b.jpg');

        $this->unlocked()->get(route('profile.collection', ['username' => $this->owner->username, 'collection_id' => $this->collection->id]))
            ->assertOk()
            ->assertSee('data-gallery-select', false)
            ->assertSee('Select photos')
            ->assertSee('Download all')
            ->assertSee('Ceremony')
            ->assertSee('data-select-photo', false);

        $this->actingAs($this->owner)->get(route('sets.detail', $this->ceremony))->assertOk()->assertSee('Download set');
    }

    public function test_paid_download_links_still_last_their_configured_days(): void
    {
        $this->assertSame(30, (int) config('downloads.link_days'));
        $order = \App\Models\Order::factory()->create(['user_id' => $this->owner->id, 'paid_at' => now()]);

        $this->assertTrue($order->downloadsExpireAt()->isSameDay(now()->addDays(30)));
    }
}
