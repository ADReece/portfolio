<?php

namespace Tests\Feature\Favourites;

use App\Models\Collection;
use App\Models\FavouriteList;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientFavouritesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Collection $collection;
    /** @var list<Photo> */
    private array $photos = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        $this->owner = User::factory()->create(['notify_favourites' => true]);
        $this->collection = Collection::factory()->private()->published()->create(['user_id' => $this->owner->id, 'name' => 'Smith Wedding']);
        $set = Set::create(['collection_id' => $this->collection->id, 'name' => 'Main']);
        foreach (['DSC_0001.JPG', 'DSC_0002.JPG', null] as $i => $name) {
            $this->photos[] = Photo::create(['user_id' => $this->owner->id, 'set_id' => $set->id, 'url' => "photos/1/stored-{$i}.jpg", 'private' => true, 'size' => 1, 'original_filename' => $name]);
        }
    }

    private function unlocked(): static
    {
        return $this->withCredentials()->withSession(['unlocked_collections' => [(string) $this->collection->id]]);
    }

    private function toggle(Photo $photo, ?string $cookie = null)
    {
        $request = $this->unlocked();
        if ($cookie) {
            $request = $request->withCookie('pf_fav_'.str_replace('-', '', $this->collection->id), $cookie);
        }

        return $request->postJson(route('favourites.toggle', [$this->collection, $photo]));
    }

    public function test_locked_galleries_refuse_favourites(): void
    {
        $this->postJson(route('favourites.toggle', [$this->collection, $this->photos[0]]))->assertNotFound();
        $this->getJson(route('favourites.show', $this->collection))->assertNotFound();
    }

    public function test_hearting_and_unhearting_keeps_one_list_per_browser(): void
    {
        $first = $this->toggle($this->photos[0])->assertOk()->assertJsonPath('ids', [(string) $this->photos[0]->id]);
        $listId = FavouriteList::sole()->id;
        $first->assertCookie('pf_fav_'.str_replace('-', '', $this->collection->id), $listId);

        $this->toggle($this->photos[1], $listId)->assertJsonCount(2, 'ids');
        $this->toggle($this->photos[0], $listId)->assertJsonPath('ids', [(string) $this->photos[1]->id]);
        $this->assertSame(1, FavouriteList::count());
    }

    public function test_photos_from_other_galleries_cannot_be_hearted(): void
    {
        $other = Collection::factory()->published()->create(['user_id' => $this->owner->id]);
        $photo = Photo::create(['user_id' => $this->owner->id, 'set_id' => Set::create(['collection_id' => $other->id, 'name' => 'X'])->id, 'url' => 'photos/1/x.jpg', 'private' => false, 'size' => 1]);

        $this->toggle($photo)->assertNotFound();
    }

    public function test_sending_favourites_emails_the_photographer(): void
    {
        $listId = $this->toggle($this->photos[0])->json() ? FavouriteList::sole()->id : null;
        $this->toggle($this->photos[1], $listId);

        $this->unlocked()->withCookie('pf_fav_'.str_replace('-', '', $this->collection->id), $listId)
            ->postJson(route('favourites.submit', $this->collection), ['name' => 'Sam', 'email' => 'sam@example.com', 'note' => 'Album please'])
            ->assertOk()->assertJsonPath('name', 'Sam');

        $list = FavouriteList::sole();
        $this->assertNotNull($list->submitted_at);
        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();
        $this->assertSame($this->owner->email, $email->getTo()[0]->getAddress());
        $this->assertSame('sam@example.com', $email->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('Album please', $email->getHtmlBody());
    }

    public function test_no_email_when_the_photographer_turned_it_off_and_nothing_to_send_is_refused(): void
    {
        $this->owner->update(['notify_favourites' => false]);
        $this->unlocked()->postJson(route('favourites.submit', $this->collection))->assertStatus(422);

        $this->toggle($this->photos[0]);
        $this->unlocked()->withCookie('pf_fav_'.str_replace('-', '', $this->collection->id), FavouriteList::sole()->id)
            ->postJson(route('favourites.submit', $this->collection))->assertOk();
        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());
    }

    public function test_hearting_does_not_use_up_the_send_limit(): void
    {
        $this->toggle($this->photos[0]);
        $listId = FavouriteList::sole()->id;
        for ($i = 0; $i < 9; $i++) {
            $this->toggle($this->photos[1], $listId)->assertOk();
        }
        $this->unlocked()->withCookie('pf_fav_'.str_replace('-', '', $this->collection->id), $listId)
            ->postJson(route('favourites.submit', $this->collection))->assertOk();
    }

    public function test_writes_are_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->unlocked()->postJson(route('favourites.submit', $this->collection));
        }
        $this->unlocked()->postJson(route('favourites.submit', $this->collection))->assertStatus(429);
    }

    public function test_photographer_sees_lists_and_exports_filenames_for_lightroom(): void
    {
        $this->toggle($this->photos[0]);
        $listId = FavouriteList::sole()->id;
        $this->toggle($this->photos[2], $listId);

        $this->actingAs($this->owner)->get(route('collections.client-favourites.index', $this->collection->id))
            ->assertOk()->assertSee('A client')->assertSee('2 photos');
        $this->actingAs($this->owner)->get(route('collections.client-favourites.show', [$this->collection->id, $listId]))
            ->assertOk()->assertSee('DSC_0001, stored-2');
        $export = $this->actingAs($this->owner)->get(route('collections.client-favourites.export', [$this->collection->id, $listId]));
        $export->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->assertSame("DSC_0001.JPG\nstored-2.jpg\n", $export->getContent());

        $this->actingAs(User::factory()->create())->get(route('collections.client-favourites.show', [$this->collection->id, $listId]))->assertNotFound();
    }

    public function test_photographer_can_turn_favourites_off(): void
    {
        $this->actingAs($this->owner)->patch(route('collections.client-favourites.settings', $this->collection->id), ['notify_favourites' => '1'])
            ->assertSessionHas('success');
        $this->assertFalse($this->collection->fresh()->favourites_enabled);
        auth()->logout();
        $this->toggle($this->photos[0])->assertNotFound();
    }

    public function test_gallery_shows_hearts_only_when_enabled(): void
    {
        $url = route('profile.collection', ['username' => $this->owner->username, 'collection_id' => $this->collection->id]);
        $this->unlocked()->get($url)->assertOk()->assertSee('favourites-bar', false)->assertSee('data-favourite=', false);

        $this->collection->update(['favourites_enabled' => false]);
        $this->unlocked()->get($url)->assertOk()->assertDontSee('favourites-bar', false);
    }

    public function test_uploads_keep_the_camera_filename(): void
    {
        Storage::fake('s3');
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $this->owner->update(['subscription_plan_id' => \App\Models\SubscriptionPlan::where('slug', 'free')->value('id')]);
        $set = $this->collection->sets()->first();
        $this->actingAs($this->owner)->post(route('upload-files'), [
            'file' => UploadedFile::fake()->image('IMG_4821.jpg', 10, 10), 'set_id' => $set->id,
        ])->assertSuccessful();

        $this->assertSame('IMG_4821.jpg', Photo::whereNotIn('id', array_map(fn ($p) => $p->id, $this->photos))->sole()->original_filename);
    }
}
