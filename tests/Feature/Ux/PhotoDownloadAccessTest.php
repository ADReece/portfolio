<?php

namespace Tests\Feature\Ux;

use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use App\Support\GalleryAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoDownloadAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
            'key' => 'test', 'secret' => 'test', 'region' => 'eu-west-2', 'bucket' => 'test-bucket',
        ])]);
    }

    private function photoIn(Collection $collection, array $attrs = []): Photo
    {
        $set = Set::create(['collection_id' => $collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);

        return Photo::create(array_merge([
            'user_id' => $collection->user_id, 'set_id' => $set->id, 'url' => 'photos/a.jpg',
            'caption' => 'A', 'private' => false, 'size' => 10, 'tags' => null,
        ], $attrs));
    }

    public function test_private_gallery_photo_download_needs_unlock(): void
    {
        $owner = User::factory()->create();
        $collection = Collection::factory()->private()->published()->create(['user_id' => $owner->id]);
        $photo = $this->photoIn($collection);

        $this->get(route('photos.download-options', $photo))->assertNotFound();
        $this->postJson(route('photos.request-download', $photo), ['email' => 'x@example.com'])->assertNotFound();

        $this->post(route('profile.collection.unlock', ['username' => $owner->username, 'collection_id' => $collection->id]), ['password' => 'password123']);

        $this->get(route('photos.download-options', $photo))->assertOk();
    }

    public function test_draft_collection_photos_are_not_downloadable(): void
    {
        $photo = $this->photoIn(Collection::factory()->create());

        $this->get(route('photos.download-options', $photo))->assertNotFound();
    }

    public function test_watermarked_photos_download_the_watermarked_file(): void
    {
        $collection = Collection::factory()->published()->create(['watermarked' => true]);
        $photo = $this->photoIn($collection);

        $this->assertSame('watermarked/a.jpg', GalleryAccess::downloadKey($photo));
        $this->assertSame('photos/a.jpg', GalleryAccess::downloadKey($this->photoIn(Collection::factory()->published()->create())));
    }
}
