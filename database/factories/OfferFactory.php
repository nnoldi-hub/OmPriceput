<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
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
            'user_id' => User::factory(),
            'title' => 'Oferta interventie - '.fake()->words(2, true),
            'status' => fake()->randomElement(['draft', 'sent', 'accepted', 'rejected', 'expired']),
            'total_amount' => fake()->randomFloat(2, 500, 8000),
            'valid_until' => now()->addDays(30),
        ];
    }
}
