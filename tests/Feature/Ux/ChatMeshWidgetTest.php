<?php

namespace Tests\Feature\Ux;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMeshWidgetTest extends TestCase
{
    use RefreshDatabase;

    private const SCRIPT = 'data-chatmesh-key="pk_test_widget"';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.chatmesh.key' => 'pk_test_widget',
            'services.chatmesh.url' => 'https://chatmesh.example.test',
        ]);
    }

    public function test_widget_is_on_the_site_pages(): void
    {
        foreach (['/', '/about', '/terms', '/privacy', '/sla', '/login', '/register', '/forgot-password', '/pricing'] as $path) {
            $this->get($path)->assertOk()->assertSee(self::SCRIPT, false);
        }

        $this->get('/')->assertSee('https://chatmesh.example.test/widget.js', false);
    }

    public function test_widget_is_not_on_photographer_or_client_pages(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create([
            'user_id' => $user->id,
            'private' => true,
            'password' => bcrypt('secret1234'),
            'status' => 'Published',
        ]);

        $this->get('/@'.$user->username)->assertOk()->assertDontSee(self::SCRIPT, false);
        $this->get('/@'.$user->username.'/collections')->assertOk()->assertDontSee(self::SCRIPT, false);
        $this->get(route('profile.collection', ['username' => $user->username, 'collection_id' => $collection->id]))
            ->assertOk()->assertDontSee(self::SCRIPT, false);
    }

    public function test_widget_is_not_in_the_photographer_workspace(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')->assertOk()->assertDontSee(self::SCRIPT, false);
    }

    public function test_signed_in_visitors_are_identified_to_the_widget(): void
    {
        $user = User::factory()->create(['name' => 'Ada Photo', 'email' => 'ada@example.com']);

        $this->actingAs($user)->get('/pricing')->assertOk()->assertSee('ada@example.com', false);
    }

    public function test_nothing_renders_without_a_key(): void
    {
        config(['services.chatmesh.key' => null]);

        $this->get('/')->assertOk()->assertDontSee('widget.js', false);
    }
}
