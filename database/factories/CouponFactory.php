<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('PROMO##??')),
            'name' => $this->faker->words(2, true),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_age_based' => false,
            'is_active' => true,
        ];
    }
}
