<?php

namespace Database\Factories;

use App\Models\CustomDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomDomainFactory extends Factory
{
    protected $model = CustomDomain::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'hostname' => $this->faker->unique()->domainWord().'-photos.com',
            'include_www' => true,
            'primary_www' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => CustomDomain::VERIFIED, 'verified_at' => now()]);
    }
}
