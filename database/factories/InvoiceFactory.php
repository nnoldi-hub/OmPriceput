<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'invoice_number' => 'OP-'.now()->format('Y').'-'.fake()->unique()->numberBetween(1000, 9999),
            'amount' => fake()->randomFloat(2, 200, 6000),
            'status' => fake()->randomElement(['unpaid', 'paid', 'cancelled']),
            'issued_at' => now()->subDays(fake()->numberBetween(0, 60)),
            'due_at' => now()->addDays(fake()->numberBetween(-10, 30)),
        ];
    }
}
