<?php

namespace Tests\Feature\Ux;

use App\Jobs\GenerateCollectionArchive;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClientArchiveDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_with_unlocked_gallery_can_request_archive(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $collection = Collection::factory()->private()->published()->create(['user_id' => $owner->id]);

        $this->post(route('profile.collection.unlock', ['username' => $owner->username, 'collection_id' => $collection->id]), [
            'password' => 'password123',
        ]);

        $this->postJson(route('collections.request-archive', $collection->id), ['email' => 'client@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(GenerateCollectionArchive::class);
    }

    public function test_archive_request_requires_unlocked_gallery(): void
    {
        Queue::fake();
        $collection = Collection::factory()->private()->published()->create();

        $this->postJson(route('collections.request-archive', $collection->id), ['email' => 'client@example.com'])
            ->assertForbidden();

        // Another logged-in user can't request someone else's archive either.
        $this->actingAs(User::factory()->create())
            ->postJson(route('collections.request-archive', $collection->id), ['email' => 'client@example.com'])
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_archive_pages_require_a_signed_link(): void
    {
        $collection = Collection::factory()->private()->published()->create();
        $filename = 'collection-'.$collection->id.'-'.time().'.zip';

        $this->get(route('collections.archive-download-page', ['collection' => $collection->id, 'filename' => $filename]))
            ->assertForbidden();
        $this->get(route('collections.download-archive', ['collection' => $collection->id, 'filename' => $filename]))
            ->assertForbidden();
    }
}
