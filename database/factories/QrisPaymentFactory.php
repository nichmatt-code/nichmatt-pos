<?php

namespace Database\Factories;

use App\Models\QrisPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrisPayment>
 */
class QrisPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => 'QRIS-'.$this->faker->unique()->numerify('########-###'),
            'amount' => $this->faker->numberBetween(10000, 200000),
            'status' => 'pending',
            'cart_snapshot' => [],
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
