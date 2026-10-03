<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installation>
 */
class InstallationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'type' => fake()->randomElement(Installation::TYPES),
            'requested_type' => null,
            'address' => fake()->address(),
            'scheduled_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'status' => 'scheduled',
            'checklist' => Installation::defaultChecklist(),
        ];
    }
}
