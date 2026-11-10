<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileController;
use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PortfolioPrivacyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_display_mode_only_shows_published_visible_public_collections(): void
    {
        $user = User::factory()->create([
            'portfolio_display_mode' => 'collections',
        ]);

        $visible = Collection::factory()->create([
            'user_id' => $user->id,
            'status' => 'Published',
            'hide_from_portfolio' => false,
            'private' => false,
        ]);

        Collection::factory()->create([
            'user_id' => $user->id,
            'status' => 'Draft',
            'hide_from_portfolio' => false,
            'private' => false,
        ]);

        Collection::factory()->create([
            'user_id' => $user->id,
            'status' => 'Published',
            'hide_from_portfolio' => true,
            'private' => false,
        ]);

        Collection::factory()->create([
            'user_id' => $user->id,
            'status' => 'Published',
            'hide_from_portfolio' => false,
            'private' => true,
            'password' => bcrypt('secret1234'),
        ]);

        $response = $this->get(route('profile.view', ['username' => $user->username]));

        $response->assertOk();
        $response->assertViewIs('profile.view-collections');
        $response->assertViewHas('collections', function ($collections) use ($visible) {
            return $collections->count() === 1 && $collections->first()->id === $visible->id;
        });
    }

    public function test_private_collection_route_requires_password_before_showing_gallery(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'user_id' => $user->id,
            'private' => true,
            'password' => bcrypt('secret1234'),
            'status' => 'Published',
        ]);

        $response = $this->get(route('profile.collection', [
            'username' => $user->username,
            'collection_id' => $collection->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('collections.frontend.password');
    }

    public function test_public_collections_page_lists_only_visible_collections(): void
    {
        $user = User::factory()->create(['portfolio_display_mode' => 'grid']);
        $visible = Collection::factory()->published()->create(['user_id' => $user->id]);
        Collection::factory()->private()->published()->create(['user_id' => $user->id]);
        Collection::factory()->create(['user_id' => $user->id]); // draft

        $this->get(route('profile.collections', ['username' => $user->username]))
            ->assertOk()
            ->assertViewIs('profile.view-collections')
            ->assertViewHas('collections', fn ($c) => $c->count() === 1 && $c->first()->id === $visible->id);
    }

    private function makePrivateCollection(): array
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'user_id' => $user->id,
            'private' => true,
            'password' => bcrypt('secret1234'),
            'status' => 'Published',
        ]);

        return [$user, $collection];
    }

    public function test_private_collection_rejects_incorrect_password(): void
    {
        [$user, $collection] = $this->makePrivateCollection();
        $galleryUrl = route('profile.collection', ['username' => $user->username, 'collection_id' => $collection->id]);

        $response = $this->from($galleryUrl)->post(route('profile.collection.unlock', [
            'username' => $user->username,
            'collection_id' => $collection->id,
        ]), ['password' => 'wrong-password']);

        $response->assertRedirect($galleryUrl);
        $response->assertSessionHasErrors('password');
        $this->get($galleryUrl)->assertViewIs('collections.frontend.password');
    }

    public function test_private_collection_allows_access_with_valid_password(): void
    {
        [$user, $collection] = $this->makePrivateCollection();
        $galleryUrl = route('profile.collection', ['username' => $user->username, 'collection_id' => $collection->id]);

        $response = $this->post(route('profile.collection.unlock', [
            'username' => $user->username,
            'collection_id' => $collection->id,
        ]), ['password' => 'secret1234']);

        $response->assertRedirect($galleryUrl);
        $this->get($galleryUrl)->assertOk()->assertViewIs('collections.frontend.show');
    }

    public function test_password_in_query_string_is_not_accepted(): void
    {
        [$user, $collection] = $this->makePrivateCollection();

        $this->get(route('profile.collection', [
            'username' => $user->username,
            'collection_id' => $collection->id,
            'password' => 'secret1234',
        ]))->assertViewIs('collections.frontend.password');
    }

    public function test_signed_share_link_unlocks_private_collection(): void
    {
        [$user, $collection] = $this->makePrivateCollection();

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('profile.collection', now()->addDay(), [
            'username' => $user->username,
            'collection_id' => $collection->id,
        ]);

        $this->get($signedUrl)->assertOk()->assertViewIs('collections.frontend.show');

        // The unlock persists for the session, so the plain URL now works too.
        $this->get(route('profile.collection', ['username' => $user->username, 'collection_id' => $collection->id]))
            ->assertViewIs('collections.frontend.show');
    }

    public function test_tampered_share_link_is_rejected(): void
    {
        [$user, $collection] = $this->makePrivateCollection();

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('profile.collection', now()->addDay(), [
            'username' => $user->username,
            'collection_id' => $collection->id,
        ]);

        $this->get($signedUrl.'x')->assertViewIs('collections.frontend.password');
    }

    public function test_public_collection_filters_out_private_photos(): void
    {
        $user = User::factory()->create();

        $collection = Collection::factory()->create([
            'user_id' => $user->id,
            'private' => false,
            'status' => 'Published',
        ]);

        $set = Set::create([
            'collection_id' => $collection->id,
            'name' => 'Main Set',
            'hide_from_portfolio' => false,
        ]);

        $publicPhoto = Photo::create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'url' => 'photos/public-photo.jpg',
            'caption' => 'Public',
            'description' => null,
            'private' => false,
            'size' => 1024,
            'tags' => null,
        ]);

        Photo::create([
            'user_id' => $user->id,
            'set_id' => $set->id,
            'url' => 'photos/private-photo.jpg',
            'caption' => 'Private',
            'description' => null,
            'private' => true,
            'size' => 1024,
            'tags' => null,
        ]);

        $request = Request::create('/@'.$user->username.'/collections/'.$collection->id, 'GET');
        $view = app(ProfileController::class)->collection($request, $user->username, $collection->id);
        $resolvedCollection = $view->getData()['collection'];
        $photos = $resolvedCollection->sets->first()->photos;

        $this->assertSame('collections.frontend.show', $view->name());
        $this->assertCount(1, $photos);
        $this->assertSame($publicPhoto->id, $photos->first()->id);
    }
}
