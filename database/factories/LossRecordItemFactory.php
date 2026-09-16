<?php

namespace Database\Factories;

use App\Models\LossRecordItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LossRecordItem>
 */
class LossRecordItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = $this->faker->numberBetween(1000, 20000);
        $qty = $this->faker->numberBetween(1, 5);

        return [
            'product_name' => $this->faker->words(2, true),
            'qty' => $qty,
            'cost_price' => $costPrice,
            'subtotal_cost' => $costPrice * $qty,
        ];
    }
}
