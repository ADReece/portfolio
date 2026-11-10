<?php

namespace Tests\Feature\Photos;

use App\Jobs\ProcessImageUpload;
use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads are stored as-is by the request; the thumbnail and watermarked copy
 * are made by a queued job with bounded memory (a 6000×4000 camera JPEG used
 * to OOM-kill the web container while it was processed in the request).
 */
class PhotoProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Set $set;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        Storage::fake('s3');
        $this->user = User::factory()->create(['username' => 'shutterbug', 'subscription_plan_id' => SubscriptionPlan::where('slug', 'free')->value('id')]);
        $collection = Collection::factory()->published()->create(['user_id' => $this->user->id]);
        $this->set = Set::create(['collection_id' => $collection->id, 'name' => 'Main', 'hide_from_portfolio' => false]);
    }

    /** A real camera-sized JPEG (6000×4000 by default). */
    private function largeJpeg(int $w = 6000, int $h = 4000): string
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Needs the imagick extension (installed in production and CI).');
        }
        $im = new \Imagick();
        $im->newPseudoImage($w, $h, 'gradient:#204060-#e0c080');
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(90);
        $path = tempnam(sys_get_temp_dir(), 'big').'.jpg';
        $im->writeImage($path);
        $im->clear();

        return $path;
    }

    public function test_upload_stores_the_original_and_queues_processing_without_decoding_it(): void
    {
        Queue::fake();
        $path = $this->largeJpeg();
        $file = new UploadedFile($path, 'IMG_0001.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($this->user)->post(route('upload-files'), ['file' => $file, 'set_id' => $this->set->id], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['success' => true, 'status' => 'processing']);

        $photo = Photo::sole();
        $this->assertSame($response->json('photo_id'), (string) $photo->id);
        $this->assertSame(Photo::PROCESSING, $photo->status);
        $this->assertStringStartsWith('photos/'.$this->user->id.'/', $photo->url);
        // The original is stored byte-for-byte, not re-encoded.
        $this->assertSame(md5_file($path), md5(Storage::disk('s3')->get($photo->url)));
        Queue::assertPushed(ProcessImageUpload::class, fn ($job) => $job->photoId === (string) $photo->id);
    }

    public function test_large_photo_is_processed_by_the_job(): void
    {
        $path = $this->largeJpeg();
        $photo = $this->set->photos()->create(['user_id' => $this->user->id, 'url' => 'photos/'.$this->user->id.'/big.jpg', 'private' => false, 'size' => filesize($path), 'status' => Photo::PROCESSING]);
        Storage::disk('s3')->put($photo->url, file_get_contents($path));

        (new ProcessImageUpload($photo->id))->handle();

        $this->assertSame(Photo::READY, $photo->fresh()->status);
        foreach (['thumbnails' => 800, 'watermarked' => ProcessImageUpload::DISPLAY_EDGE] as $folder => $edge) {
            $key = ProcessImageUpload::derivativeKey($photo->url, $folder);
            Storage::disk('s3')->assertExists($key);
            $im = new \Imagick();
            $im->readImageBlob(Storage::disk('s3')->get($key));
            $this->assertSame($edge, max($im->getImageWidth(), $im->getImageHeight()), $folder);
            $this->assertEqualsWithDelta(1.5, $im->getImageWidth() / $im->getImageHeight(), 0.01, 'aspect ratio kept');
        }
        // The original is untouched.
        $this->assertSame(md5_file($path), md5(Storage::disk('s3')->get($photo->url)));
    }

    public function test_an_unreadable_photo_ends_up_failed_with_a_message(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Needs the imagick extension.');
        }
        $photo = $this->set->photos()->create(['user_id' => $this->user->id, 'url' => 'photos/'.$this->user->id.'/broken.jpg', 'private' => false, 'size' => 10, 'status' => Photo::PROCESSING]);
        Storage::disk('s3')->put($photo->url, 'not really a jpeg');

        $job = new ProcessImageUpload($photo->id);
        try {
            $job->handle();
            $this->fail('Processing a broken file should throw');
        } catch (\Throwable $e) {
            $job->failed($e); // what the queue does after the last attempt
        }

        $photo->refresh();
        $this->assertSame(Photo::FAILED, $photo->status);
        $this->assertSame(Photo::FAILED_MESSAGE, $photo->processing_error);
        Storage::disk('s3')->assertMissing(ProcessImageUpload::derivativeKey($photo->url, 'thumbnails'));
    }

    public function test_owner_sees_processing_and_failed_photos_and_the_public_never_does(): void
    {
        $processing = $this->set->photos()->create(['user_id' => $this->user->id, 'url' => 'photos/x/a.jpg', 'private' => false, 'size' => 1, 'status' => Photo::PROCESSING]);
        $failed = $this->set->photos()->create(['user_id' => $this->user->id, 'url' => 'photos/x/b.jpg', 'private' => false, 'size' => 1, 'status' => Photo::FAILED, 'processing_error' => Photo::FAILED_MESSAGE]);

        \Livewire\Livewire::actingAs($this->user)->test(\App\Livewire\SetPhotos::class, ['setId' => $this->set->id])
            ->assertSee('Preparing…')
            ->assertSee(Photo::FAILED_MESSAGE)
            ->assertSee('Remove');

        $this->assertSame(0, Photo::query()->ready()->count());
        \Livewire\Livewire::test(\App\Livewire\MasonryGrid::class, ['userId' => $this->user->id])->assertSet('totalCount', 0);
        $this->get(route('photos.download-options', $processing))->assertNotFound();
        $this->get(route('photos.download-options', $failed))->assertNotFound();
    }

    public function test_existing_photos_are_ready(): void
    {
        $photo = Photo::factory()->create();

        $this->assertSame(Photo::READY, $photo->fresh()->status);
    }

    public function test_a_24_megapixel_jpeg_is_processed_within_200_mb(): void
    {
        if (! is_readable('/proc/self/status')) {
            $this->markTestSkipped('Needs /proc to read peak memory.');
        }
        $path = $this->largeJpeg();

        $result = \Illuminate\Support\Facades\Process::timeout(120)
            ->run([PHP_BINARY, '-d', 'memory_limit=512M', __DIR__.'/scripts/measure-derivatives.php', $path]);
        $this->assertTrue($result->successful(), $result->errorOutput());
        $data = json_decode($result->output(), true);

        $this->assertNotNull($data['peak_kb'] ?? null, $result->output());
        $this->assertLessThan(200 * 1024, $data['peak_kb'], 'peak RSS was '.round($data['peak_kb'] / 1024).' MB');
        $this->assertGreaterThan(0, $data['thumbnail_bytes']);
        $this->assertGreaterThan(0, $data['watermarked_bytes']);
    }

    public function test_uploading_eight_photos_creates_eight_rows_and_queues_eight_jobs(): void
    {
        Queue::fake();
        $this->user->forceFill(['feature_override' => true])->save(); // no free-plan photo limit in the way
        $path = $this->largeJpeg(1200, 800);

        $started = microtime(true);
        foreach (range(1, 8) as $n) {
            $this->actingAs($this->user)->post(route('upload-files'), [
                'file' => new UploadedFile($path, "IMG_{$n}.jpg", 'image/jpeg', null, true),
                'set_id' => $this->set->id,
            ], ['Accept' => 'application/json'])->assertOk()->assertJson(['status' => 'processing']);
        }

        $this->assertSame(8, Photo::where('status', Photo::PROCESSING)->count());
        Queue::assertPushed(ProcessImageUpload::class, 8);
        $this->assertSame(8, Photo::pluck('id')->unique()->count());
        // No image work in the request: eight uploads together stay quick.
        $this->assertLessThan(8, microtime(true) - $started);
    }

    public function test_a_failed_photo_can_be_retried_by_its_owner(): void
    {
        Queue::fake();
        $photo = $this->set->photos()->create(['user_id' => $this->user->id, 'url' => 'photos/'.$this->user->id.'/retry.jpg', 'private' => false, 'size' => 1, 'status' => Photo::FAILED, 'processing_error' => Photo::FAILED_MESSAGE]);
        Storage::disk('s3')->put($photo->url, 'original bytes');

        \Livewire\Livewire::actingAs($this->user)->test(\App\Livewire\SetPhotos::class, ['setId' => $this->set->id])
            ->assertSee('Retry')
            ->call('retryPhoto', (string) $photo->id)
            ->assertSee('Preparing…');

        $this->assertSame(Photo::PROCESSING, $photo->fresh()->status);
        $this->assertNull($photo->fresh()->processing_error);
        Queue::assertPushed(ProcessImageUpload::class, fn ($job) => $job->photoId === (string) $photo->id);

        // Someone else can't.
        $other = User::factory()->create();
        $photo->update(['status' => Photo::FAILED]);
        \Livewire\Livewire::actingAs($other)->test(\App\Livewire\SetPhotos::class, ['setId' => $this->set->id])->assertForbidden();
    }

    public function test_job_timeout_stays_below_the_queue_retry_window(): void
    {
        $this->assertLessThan(config('queue.connections.database.retry_after'), (new ProcessImageUpload('x'))->timeout);
    }
}
