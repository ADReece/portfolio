<?php

namespace Tests\Feature\Storage;

use App\Http\Middleware\ContentSecurityPolicy;
use App\Jobs\GenerateCollectionArchive;
use App\Models\Collection;
use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Media on S3-compatible object storage (Hetzner fsn1): private, signed links, no silent failures. */
class ObjectStorageReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function useBucket(array $overrides = []): void
    {
        config(['filesystems.disks.s3' => $overrides + [
            'driver' => 's3', 'key' => 'k', 'secret' => 's', 'region' => 'fsn1', 'bucket' => 'portfolio-media',
            'url' => null, 'endpoint' => 'https://fsn1.your-objectstorage.com', 'use_path_style_endpoint' => true,
            'visibility' => 'private', 'throw' => true,
        ]]);
        Storage::forgetDisk('s3');
    }

    public function test_the_s3_disk_is_private_and_throws_on_errors(): void
    {
        // The real config, as production builds it with AWS_BUCKET set.
        $before = [$_ENV['AWS_BUCKET'] ?? null, $_SERVER['AWS_BUCKET'] ?? null];
        $_ENV['AWS_BUCKET'] = $_SERVER['AWS_BUCKET'] = 'portfolio-media';
        try {
            $config = require config_path('filesystems.php');
        } finally {
            [$_ENV['AWS_BUCKET'], $_SERVER['AWS_BUCKET']] = $before;
        }

        $this->assertSame('s3', $config['disks']['s3']['driver']);
        $this->assertTrue($config['disks']['s3']['throw']);
        $this->assertSame('private', $config['disks']['s3']['visibility']);
    }

    public function test_photo_links_are_signed_against_the_endpoint(): void
    {
        $this->useBucket();
        $photo = new Photo(['url' => 'photos/1/a.jpg']);

        foreach ([$photo->getAwsThumbnail(), $photo->getAwsWatermarked(), $photo->getAwsMedia()] as $url) {
            $this->assertStringStartsWith('https://fsn1.your-objectstorage.com/portfolio-media/', $url);
            $this->assertStringContainsString('X-Amz-Signature=', $url);
        }
    }

    public function test_csp_allows_the_storage_host(): void
    {
        $this->useBucket();
        $this->assertStringContainsString('https://fsn1.your-objectstorage.com', ContentSecurityPolicy::policy());

        $this->useBucket(['use_path_style_endpoint' => false]);
        $this->assertStringContainsString('https://portfolio-media.fsn1.your-objectstorage.com', ContentSecurityPolicy::policy());

        $this->useBucket(['url' => 'https://media.example.com']);
        $this->assertStringContainsString('https://media.example.com', ContentSecurityPolicy::policy());
    }

    public function test_local_disk_needs_no_storage_host_in_csp(): void
    {
        config(['filesystems.disks.s3' => ['driver' => 'local', 'root' => storage_path('app/media'), 'url' => 'http://localhost/media']]);

        $this->assertStringNotContainsString('your-objectstorage', ContentSecurityPolicy::policy());
    }

    public function test_download_all_reads_from_storage_and_gives_watermarked_copies(): void
    {
        Storage::fake('s3');
        Queue::fake(); // the 48-hour cleanup
        config(['mail.default' => 'array']);
        $owner = User::factory()->create();
        $collection = Collection::factory()->private()->published()->create(['user_id' => $owner->id, 'watermarked' => true]);
        $set = Set::create(['collection_id' => $collection->id, 'name' => 'Main']);
        Storage::disk('s3')->put('photos/1/a.jpg', 'original-a');
        Storage::disk('s3')->put('watermarked/1/a.jpg', 'watermarked-a');
        Photo::create(['user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/1/a.jpg', 'private' => true, 'size' => 1]);
        Photo::create(['user_id' => $owner->id, 'set_id' => $set->id, 'url' => 'photos/1/b.jpg', 'private' => true, 'size' => 1, 'status' => Photo::PROCESSING]);

        (new GenerateCollectionArchive($collection->id, 'client@example.com'))->handle();

        $archives = Storage::disk('s3')->files('archives');
        $this->assertCount(1, $archives);
        $path = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($path, Storage::disk('s3')->get($archives[0]));
        $zip = new \ZipArchive();
        $zip->open($path);
        $this->assertSame(1, $zip->numFiles, 'photos still processing are left out');
        $this->assertSame('watermarked-a', $zip->getFromIndex(0));
        $this->assertSame('photo-001.jpg', $zip->getNameIndex(0));
        $this->assertCount(1, app('mailer')->getSymfonyTransport()->messages());
    }
}
