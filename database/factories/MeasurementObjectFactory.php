<?php

namespace Database\Factories;

use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasurementObjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'measurement_id' => Measurement::factory(),
            'name' => fake()->randomElement(['Living', 'Bucatarie', 'Dormitor', 'Camera copii', 'Baie', 'Hol', 'Garaj']),
            'length' => fake()->randomFloat(2, 1.5, 8),
            'width' => fake()->randomFloat(2, 1, 6),
            'height' => fake()->optional()->randomFloat(2, 2.2, 3.2),
            'quantity' => 1,
            'notes' => null,
            'position' => 0,
        ];
    }
}
