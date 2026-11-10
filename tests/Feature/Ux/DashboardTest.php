<?php

namespace Tests\Feature\Ux;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_sees_welcome_and_setup_checklist(): void
    {
        $user = User::factory()->create(['name' => 'Ava Reid']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome, Ava')
            ->assertSee('Get set up')
            ->assertSee('Create your first collection');
    }

    public function test_checklist_marks_completed_steps(): void
    {
        $user = User::factory()->create();
        Collection::factory()->published()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('steps', fn ($steps) => $steps[0]['done'] && $steps[2]['done'] && ! $steps[1]['done']);
    }
}
