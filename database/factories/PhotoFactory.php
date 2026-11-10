<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        // Mirrors the photos table: url is an S3 key; thumbnails and
        // watermarked copies are derived from it (see Photo::getAwsThumbnail).
        return [
            'user_id' => User::factory(),
            'set_id' => Set::factory(),
            'url' => 'photos/' . fake()->md5() . '.jpg',
            'caption' => fake()->sentence(),
            'description' => fake()->sentence(),
            'private' => false,
            'size' => fake()->numberBetween(500000, 5000000),
            'tags' => '',
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'private' => true,
        ]);
    }
}
