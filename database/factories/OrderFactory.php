<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 20, 200);
        $fee = round($total * 0.10, 2);

        return [
            'user_id' => User::factory(),
            'customer_email' => fake()->safeEmail(),
            'customer_name' => fake()->name(),
            'total' => $total,
            'platform_fee' => $fee,
            'photographer_amount' => $total - $fee,
            'status' => 'pending',
        ];
    }
}
