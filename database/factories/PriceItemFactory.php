<?php

namespace Database\Factories;

use App\Models\MeasurementObject;
use App\Models\PriceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceItem>
 */
class PriceItemFactory extends Factory
{
    protected $model = PriceItem::class;

    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(MeasurementObject::TYPES));

        return [
            'object_type' => $type,
            'name' => fake()->words(2, true),
            'unit' => MeasurementObject::TYPES[$type]['unit'] ?? 'buc',
            'price' => fake()->randomFloat(2, 5, 300),
            'is_active' => true,
            'position' => 0,
        ];
    }
}
