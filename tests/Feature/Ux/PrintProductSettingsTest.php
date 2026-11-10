<?php

namespace Tests\Feature\Ux;

use App\Models\ProdigiProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintProductSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_the_page_keeps_saved_prices_and_choices(): void
    {
        $user = User::factory()->create();
        $a = ProdigiProduct::query()->create(['sku' => 'A', 'name' => 'Print A', 'category' => 'Prints', 'is_active' => true]);
        $b = ProdigiProduct::query()->create(['sku' => 'B', 'name' => 'Print B', 'category' => 'Prints', 'is_active' => true]);
        $user->prodigiProducts()->attach($a->id, ['is_enabled' => false, 'retail_price' => 45]);

        $this->actingAs($user)->get(route('profile.prodigi-products.edit'))->assertOk();

        $pivot = $user->prodigiProducts()->get()->keyBy('id');
        $this->assertFalse((bool) $pivot[$a->id]->pivot->is_enabled);
        $this->assertEquals(45, (float) $pivot[$a->id]->pivot->retail_price);
        // New catalogue products are still added with defaults.
        $this->assertTrue((bool) $pivot[$b->id]->pivot->is_enabled);
    }
}
