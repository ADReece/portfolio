<?php

namespace Tests\Feature\Ux;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_can_turn_copy_protection_off(): void
    {
        $user = User::factory()->create(['is_admin' => true]); // admins have every feature
        $this->assertTrue((bool) $user->fresh()->copy_protection);

        $this->actingAs($user)->patch(route('profile.customize.update'), [
            'portfolio_theme' => 'auto',
            'copy_protection' => '0',
        ])->assertRedirect(route('profile.customize'));

        $this->assertFalse((bool) $user->fresh()->copy_protection);
    }
}
