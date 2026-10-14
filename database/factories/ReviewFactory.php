<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'token' => Review::generateToken(),
            'status' => Review::STATUS_PENDING,
            'requested_at' => now()->subDay(),
        ];
    }

    /** Clientul a răspuns, recenzia așteaptă moderarea. */
    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => Review::STATUS_SUBMITTED,
            'rating' => fake()->numberBetween(4, 5),
            'comment' => fake()->sentence(),
            'author_name' => fake()->name(),
            'city' => fake()->city(),
            'service_label' => 'Montaj mobilier',
            'consent_to_publish' => true,
            'submitted_at' => now(),
        ]);
    }

    /** Recenzie aprobată și publicată. */
    public function approved(): static
    {
        return $this->submitted()->state(fn () => [
            'status' => Review::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
