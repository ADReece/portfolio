<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function can_create_collection_with_name_only()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->set('name', 'Wedding 2026')
            ->call('saveCollection')
            ->assertSet('currentStep', 2);

        $this->assertDatabaseHas('collections', [
            'user_id' => $this->user->id,
            'name' => 'Wedding 2026',
            'status' => 'Draft',
        ]);
    }

    /** @test */
    public function can_create_collection_with_date()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->set('name', 'Beach Session')
            ->set('event_date', '2026-06-15')
            ->call('saveCollection')
            ->assertSet('currentStep', 2);

        $this->assertDatabaseHas('collections', [
            'user_id' => $this->user->id,
            'name' => 'Beach Session',
            'event_date' => '2026-06-15',
        ]);
    }

    /** @test */
    public function can_create_collection_with_privacy()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->set('name', 'Private Collection')
            ->set('private', true)
            ->set('password', 'secret123')
            ->call('saveCollection')
            ->assertSet('currentStep', 2);

        $collection = Collection::where('name', 'Private Collection')->firstOrFail();
        $this->assertTrue($collection->private);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $collection->password));
    }

    /** @test */
    public function password_required_if_collection_is_private()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->set('name', 'Private Collection')
            ->set('private', true)
            ->set('password', '')
            ->call('saveCollection')
            ->assertHasErrors('password');
    }

    /** @test */
    public function can_create_set_from_quick_template()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->call('createSetFromTemplate', 'Ceremony');

        $this->assertDatabaseHas('sets', [
            'collection_id' => $collection->id,
            'name' => 'Ceremony',
        ]);
    }

    /** @test */
    public function can_create_set_with_custom_name()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->set('setName', 'Custom Set Name')
            ->call('createSet');

        $this->assertDatabaseHas('sets', [
            'collection_id' => $collection->id,
            'name' => 'Custom Set Name',
        ]);
    }

    /** @test */
    public function cannot_create_duplicate_set_names()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $collection->sets()->create(['name' => 'Portraits']);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->set('setName', 'Portraits')
            ->call('createSet');

        // Verify only one Portraits set exists
        $this->assertEquals(1, $collection->sets()->where('name', 'Portraits')->count());
    }

    /** @test */
    public function can_edit_set_name()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $set = $collection->sets()->create(['name' => 'Original Name']);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->call('editSet', $set->id)
            ->assertSet('editingSetId', $set->id)
            ->set('editingSetName', 'Updated Name')
            ->call('updateSet');

        $set->refresh();
        $this->assertEquals('Updated Name', $set->name);
    }

    /** @test */
    public function can_delete_set()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $set = $collection->sets()->create(['name' => 'To Delete']);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->call('deleteSet', $set->id);

        $this->assertDatabaseMissing('sets', ['id' => $set->id]);
    }

    /** @test */
    public function cannot_edit_others_sets()
    {
        $otherUser = User::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $otherUser->id]);
        $set = $collection->sets()->create(['name' => 'Original']);

        $this->actingAs($this->user);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->assertStatus(403);
    }

    /** @test */
    public function wizard_advances_to_step_2_after_collection_save()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->assertSet('currentStep', 1)
            ->set('name', 'Test Collection')
            ->call('saveCollection')
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function wizard_starts_at_step_2_when_editing_existing_collection()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);

        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->assertSet('currentStep', 2);
    }

    /** @test */
    public function finishing_the_wizard_opens_the_new_collection()
    {
        $this->actingAs($this->user);

        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $collection->sets()->create(['name' => 'Set 1']);

        // Livewire tests return the redirect response, which Livewire client would handle
        Livewire::test('collection-wizard', ['collectionId' => $collection->id])
            ->call('finishAndRedirect')
            ->assertRedirect(route('collections.edit', $collection->id));
    }

    /** @test */
    public function reveals_advanced_options_for_private_collection()
    {
        $this->actingAs($this->user);

        Livewire::test('collection-wizard')
            ->set('name', 'Test')
            ->assertSet('private', false)
            ->set('private', true)
            ->assertSet('private', true);
    }
}
