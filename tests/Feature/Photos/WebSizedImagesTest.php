<?php

namespace Tests\Feature\Photos;

use App\Jobs\ProcessImageUpload;
use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pages show web-sized copies (not camera originals), through links browsers can cache. */
class WebSizedImagesTest extends TestCase
{
    use RefreshDatabase;

    private Collection $collection;

    private Photo $photo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $owner = User::factory()->create();
        $this->collection = Collection::factory()->published()->create(['user_id' => $owner->id]);
        $set = Set::create(['collection_id' => $this->collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
        $this->photo = Photo::create(['user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/'.$owner->id.'/a.jpg', 'private' => false, 'size' => 10]);
    }

    public function test_pages_use_the_web_copy_once_it_exists_and_watermarks_still_win(): void
    {
        $this->assertStringContainsString('photos/', $this->photo->getUri(), 'the original until the copy is made');

        $this->photo->update(['has_display' => true]);
        $this->assertStringContainsString('display/', $this->photo->fresh()->getUri());

        $this->collection->update(['watermarked' => true]);
        $this->assertStringContainsString('watermarked/', $this->photo->fresh()->load('set.collection')->getUri());
    }

    public function test_existing_photos_get_web_copies_from_the_command(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Needs the imagick extension.');
        }
        $im = new \Imagick();
        $im->newPseudoImage(4000, 3000, 'gradient:#204060-#e0c080');
        $im->setImageFormat('jpeg');
        Storage::disk('s3')->put($this->photo->url, $im->getImageBlob());

        $this->artisan('photos:make-display-copies', ['--now' => true])->expectsOutputToContain('Made 1 web-sized copy')->assertSuccessful();

        $this->assertTrue($this->photo->fresh()->has_display);
        $copy = new \Imagick();
        $copy->readImageBlob(Storage::disk('s3')->get(ProcessImageUpload::derivativeKey($this->photo->url, 'display')));
        $this->assertSame([2048, 1536], [$copy->getImageWidth(), $copy->getImageHeight()]);
        $this->assertSame('JPEG', $copy->getImageFormat());

        // Running it again does nothing more.
        $this->artisan('photos:make-display-copies')->expectsOutputToContain('Queued 0 web-sized copies')->assertSuccessful();
    }

    public function test_links_stay_the_same_for_a_while_so_browsers_can_cache_them(): void
    {
        config(['media.url_window_hours' => 6]);
        $this->travelTo(now()->utc()->setTime(13, 5));
        $first = MediaUrl::signed('thumbnails/a.jpg');

        $this->travelTo(now()->utc()->setTime(17, 55));
        $this->assertSame($first, MediaUrl::signed('thumbnails/a.jpg'), 'same link within the window');

        $this->travelTo(now()->utc()->setTime(18, 1));
        $this->assertNotSame($first, MediaUrl::signed('thumbnails/a.jpg'), 'a new link in the next window');
        // …and the old one still works for a while: it expires a window later.
        parse_str((string) parse_url($first, PHP_URL_QUERY), $query);
        $this->assertGreaterThan(now()->addHours(5)->getTimestamp(), (int) ($query['expires'] ?? $query['expiration'] ?? 0));
    }

    public function test_the_collection_cover_is_web_sized_with_a_smaller_version_for_phones(): void
    {
        $this->photo->update(['has_display' => true]);
        $this->collection->update(['cover_photo_id' => $this->photo->id]);

        $this->get(route('profile.collection', ['username' => $this->collection->user->username, 'collection_id' => $this->collection->id]))
            ->assertOk()
            ->assertSee('display/', false)
            ->assertSee('800w', false)
            ->assertSee('2048w', false);
    }

    public function test_photos_stored_outside_photos_folder_are_never_overwritten_by_a_copy(): void
    {
        $this->photo->update(['url' => 'legacy/a.jpg']);
        Storage::disk('s3')->put('legacy/a.jpg', 'the original');

        $this->artisan('photos:make-display-copies', ['--now' => true])->expectsOutputToContain('Made 0 web-sized copies')->assertSuccessful();
        (new \App\Jobs\MakeDisplayCopy($this->photo->id))->handle();

        $this->assertSame('the original', Storage::disk('s3')->get('legacy/a.jpg'));
        $this->assertFalse($this->photo->fresh()->has_display);
    }
}
