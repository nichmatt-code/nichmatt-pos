<?php

namespace Database\Factories;

use App\Models\LossRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LossRecord>
 */
class LossRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loss_no' => 'LOSS-'.$this->faker->unique()->numerify('########-###'),
            'reason' => $this->faker->sentence(),
            'total_cost_value' => 0,
        ];
    }
}
