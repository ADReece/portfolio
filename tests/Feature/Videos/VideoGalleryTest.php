<?php

namespace Tests\Feature\Videos;

use App\Jobs\ProcessVideo;
use App\Models\Collection;
use App\Models\Set;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Video;
use App\Videos\FfmpegProbe;
use App\Videos\MultipartUploads;
use App\Videos\VideoProbe;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoGalleryTest extends TestCase
{
    use RefreshDatabase;

    public FakeUploads $uploads;
    public FakeProbe $probe;
    private User $owner;
    private Set $set;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->seed(SubscriptionPlanSeeder::class);
        $this->uploads = new FakeUploads();
        $this->probe = new FakeProbe();
        $this->app->instance(MultipartUploads::class, $this->uploads);
        $this->app->instance(VideoProbe::class, $this->probe);
        config(['videos.plans.videographer' => ['max_file_mb' => 100, 'max_total_mb' => 120]]);

        $this->owner = User::factory()->create(['username' => 'filmmaker', 'subscription_plan_id' => SubscriptionPlan::where('slug', 'videographer')->value('id')]);
        $collection = Collection::factory()->published()->create(['user_id' => $this->owner->id]);
        $this->set = Set::create(['collection_id' => $collection->id, 'name' => 'Films']);
    }

    private function start(array $overrides = [])
    {
        return $this->actingAs($this->owner)->postJson(route('videos.start'), $overrides + [
            'set_id' => $this->set->id, 'filename' => 'Wedding film.mp4', 'size' => 40 * 1048576, 'type' => 'video/mp4',
        ]);
    }

    private function uploaded(): Video
    {
        $id = $this->start()->assertOk()->json('id');
        $video = Video::findOrFail($id);
        Storage::disk('s3')->put($video->key, 'film-bytes'); // the browser's parts, assembled
        $this->actingAs($this->owner)->postJson(route('videos.complete', $video), ['parts' => [
            ['part_number' => 2, 'etag' => '"b"'], ['part_number' => 1, 'etag' => '"a"'], ['part_number' => 3, 'etag' => '"c"'],
        ]])->assertOk();

        return $video->fresh();
    }

    public function test_upload_goes_straight_to_the_bucket_in_parts(): void
    {
        $response = $this->start()->assertOk()->assertJson(['part_size' => 16 * 1048576, 'parts' => 3]);
        $video = Video::findOrFail($response->json('id'));
        $this->assertSame(Video::UPLOADING, $video->status);
        $this->assertSame('videos/'.$this->owner->id.'/'.$video->id.'.mp4', $video->key);
        $this->assertSame([[$video->key, 'video/mp4']], $this->uploads->created);

        $this->actingAs($this->owner)->postJson(route('videos.parts', $video), ['part_numbers' => [1, 2]])
            ->assertOk()->assertJsonPath('urls.1', "https://bucket.test/{$video->key}?partNumber=1&uploadId=up-1");
    }

    public function test_completing_assembles_the_parts_in_order_and_makes_a_poster(): void
    {
        $video = $this->uploaded();

        $this->assertSame([1, 2, 3], array_column($this->uploads->completed[0][2], 'PartNumber'));
        $this->assertSame(Video::READY, $video->status);
        $this->assertNull($video->upload_id);
        $this->assertSame([1920, 1080, 'h264', 125], [$video->width, $video->height, $video->codec, $video->duration]);
        Storage::disk('s3')->assertExists($video->poster_key);
        $this->assertStringStartsWith('video-posters/', $video->poster_key);
        $this->assertSame('2:05', $video->durationLabel());
    }

    public function test_unsupported_codecs_are_refused_with_a_clear_message(): void
    {
        $this->probe->codec = 'prores';
        $video = $this->uploaded();

        $this->assertSame(Video::FAILED, $video->status);
        $this->assertSame(ProcessVideo::UNSUPPORTED, $video->processing_error);
    }

    public function test_plan_file_type_and_size_limits(): void
    {
        $this->start(['filename' => 'film.avi'])->assertJsonValidationErrors('file');
        $this->start(['size' => 101 * 1048576])->assertJsonValidationErrors('file');
        $this->uploaded(); // 40 MB used of 120
        $this->start(['size' => 100 * 1048576])->assertJsonValidationErrors('file');

        $photographer = User::factory()->create(['subscription_plan_id' => SubscriptionPlan::where('slug', 'photographer')->value('id')]);
        $this->actingAs($photographer)->postJson(route('videos.start'), ['set_id' => $this->set->id, 'filename' => 'a.mp4', 'size' => 1])->assertForbidden();
    }

    public function test_only_the_owner_can_touch_an_upload_and_aborting_removes_it(): void
    {
        $id = $this->start()->json('id');
        $video = Video::findOrFail($id);
        $stranger = User::factory()->create(['subscription_plan_id' => $this->owner->subscription_plan_id]);
        $this->actingAs($stranger)->postJson(route('videos.parts', $video), ['part_numbers' => [1]])->assertNotFound();
        $this->actingAs($stranger)->postJson(route('videos.start'), ['set_id' => $this->set->id, 'filename' => 'a.mp4', 'size' => 1])->assertNotFound();

        $this->actingAs($this->owner)->postJson(route('videos.abort', $video))->assertOk();
        $this->assertSame([[$video->key, 'up-1']], $this->uploads->aborted);
        $this->assertSame(0, Video::count());
    }

    public function test_clients_watch_ready_films_in_the_gallery(): void
    {
        $video = $this->uploaded();
        $video->update(['title' => 'Highlights']);
        $processing = Video::create(['user_id' => $this->owner->id, 'set_id' => $this->set->id, 'key' => 'videos/x.mp4', 'mime' => 'video/mp4', 'size' => 1, 'status' => Video::PROCESSING]);

        $url = route('profile.collection', ['username' => 'filmmaker', 'collection_id' => $this->set->collection_id]);
        $this->get($url)->assertOk()->assertSee('Highlights')->assertSee('<video', false)->assertSee($video->key, false)->assertDontSee($processing->key, false);

        // A plan without video hides them.
        $this->owner->update(['subscription_plan_id' => SubscriptionPlan::where('slug', 'photographer')->value('id')]);
        $this->get($url)->assertOk()->assertDontSee('Highlights');
    }

    public function test_photographer_manages_films_on_the_set_page(): void
    {
        $video = $this->uploaded();
        $this->actingAs($this->owner)->get(route('sets.detail', $this->set))->assertOk()->assertSee('Upload films')->assertSee('Wedding film');
        $this->actingAs($this->owner)->patch(route('videos.update', $video), ['title' => 'Ceremony'])->assertSessionHas('success');
        $this->assertSame('Ceremony', $video->fresh()->title);
        $this->actingAs($this->owner)->delete(route('videos.destroy', $video))->assertSessionHas('success');
        Storage::disk('s3')->assertMissing($video->key);
        Storage::disk('s3')->assertMissing($video->poster_key);
    }

    public function test_abandoned_uploads_are_cleared(): void
    {
        $video = Video::findOrFail($this->start()->json('id'));
        $this->travel(25)->hours();
        $this->artisan('videos:clean-uploads')->expectsOutputToContain('Cleared 1')->assertSuccessful();
        $this->assertSame([[$video->key, 'up-1']], $this->uploads->aborted);
    }

    public function test_ffmpeg_reads_details_and_a_poster(): void
    {
        if (! is_executable(trim((string) shell_exec('command -v ffmpeg')))) {
            $this->markTestSkipped('ffmpeg is installed in the production image, not here.');
        }
        $file = tempnam(sys_get_temp_dir(), 'film').'.mp4';
        exec('ffmpeg -loglevel error -y -f lavfi -i testsrc=size=640x360:rate=25 -t 4 -c:v libx264 -pix_fmt yuv420p '.escapeshellarg($file), $out, $code);
        if ($code !== 0) {
            $this->markTestSkipped('This ffmpeg can\'t encode H.264.');
        }

        $probe = new FfmpegProbe();
        $info = $probe->probe($file);
        $this->assertSame(['h264', 640, 360], [$info['codec'], $info['width'], $info['height']]);
        $this->assertEqualsWithDelta(4.0, $info['duration'], 0.2);
        $this->assertStringStartsWith("\xFF\xD8", $probe->poster($file, 1.0), 'a JPEG');
    }
}
