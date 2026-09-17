<?php

namespace Database\Factories;

use App\Models\PackageItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageItem>
 */
class PackageItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qty' => $this->faker->numberBetween(1, 3),
        ];
    }
}
