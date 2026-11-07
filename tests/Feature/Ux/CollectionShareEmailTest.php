<?php

namespace Tests\Feature\Ux;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSent;
use Tests\TestCase;

class CollectionShareEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_email_contains_signed_link_in_reading_order(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->private()->published()->create(['user_id' => $user->id, 'name' => 'Smith Wedding']);

        $sent = null;
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent = $event->message->getHtmlBody();
        });

        $this->actingAs($user)
            ->postJson(route('collections.email-client', $collection), [
                'email' => 'client@example.com',
                'name' => 'Sam',
                'message' => 'Enjoy!',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($sent, 'Expected the share email to be sent');
        $this->assertStringContainsString('signature=', $sent);
        $this->assertStringContainsString('expires=', $sent);
        $this->assertStringNotContainsString('••••', $sent);

        // Greeting, then the button, then the sign-off, then the closing tag.
        $positions = array_map(fn ($needle) => strpos($sent, $needle), ['Hi Sam', 'View my gallery', 'Best regards', '</html>']);
        $this->assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }
}
