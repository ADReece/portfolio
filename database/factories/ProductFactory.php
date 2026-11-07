<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'photo_id' => fn (array $attributes) => Photo::factory()->create(['user_id' => $attributes['user_id']])->id,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'print_poster',
            'price' => fake()->randomFloat(2, 10, 100),
            'active' => true,
        ];
    }
}
