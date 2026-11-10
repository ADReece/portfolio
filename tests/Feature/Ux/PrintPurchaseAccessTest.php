<?php

namespace Tests\Feature\Ux;

use App\Models\Collection;
use App\Models\Photo;
use App\Models\ProdigiProduct;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintPurchaseAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.connect_client_id' => 'ca_test']);
        // Photo thumbnails are presigned S3 URLs; signing is local, so fake credentials are enough.
        config(['filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
            'key' => 'test', 'secret' => 'test', 'region' => 'eu-west-2', 'bucket' => 'test-bucket',
        ])]);
    }

    private function sellingPhotoIn(Collection $collection): Photo
    {
        $owner = $collection->user;
        $owner->forceFill(['is_admin' => true])->save(); // admins have every feature

        $product = ProdigiProduct::query()->create([
            'sku' => 'GLOBAL-FAP-10x8', 'name' => '10x8 Fine Art Print', 'category' => 'Prints',
        ]);
        $owner->prodigiProducts()->attach($product->id, ['is_enabled' => true, 'retail_price' => 25]);

        $set = Set::create(['collection_id' => $collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);

        return Photo::create([
            'user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/a.jpg',
            'caption' => 'A', 'private' => false, 'size' => 10, 'tags' => null,
        ]);
    }

    public function test_private_gallery_photo_is_purchasable_once_unlocked(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->private()->published()->create(['user_id' => $owner->id, 'hide_from_portfolio' => true]);
        $photo = $this->sellingPhotoIn($collection);

        $this->get(route('print-purchase.show', $photo))->assertNotFound();

        $this->post(route('profile.collection.unlock', ['username' => $owner->username, 'collection_id' => $collection->id]), [
            'password' => 'password123',
        ]);

        $this->get(route('print-purchase.show', $photo))
            ->assertOk()
            ->assertSee('Order summary')
            ->assertSee('United Kingdom');
    }

    public function test_public_photo_is_purchasable_without_unlock(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->published()->create(['user_id' => $owner->id]);
        $photo = $this->sellingPhotoIn($collection);

        $this->get(route('print-purchase.show', $photo))->assertOk();
    }

    public function test_prints_cannot_be_bought_while_payouts_are_unavailable(): void
    {
        config(['services.stripe.connect_client_id' => null]);
        $owner = User::factory()->create();
        $collection = Collection::factory()->published()->create(['user_id' => $owner->id]);
        $photo = $this->sellingPhotoIn($collection);

        $this->get(route('print-purchase.show', $photo))->assertNotFound();
    }
}
