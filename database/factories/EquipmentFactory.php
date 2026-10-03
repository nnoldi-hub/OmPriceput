<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(array_keys(Equipment::CATEGORIES)),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'unit' => 'buc',
            'unit_price' => fake()->randomFloat(2, 20, 1500),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'minimum_stock' => 5,
        ];
    }
}
