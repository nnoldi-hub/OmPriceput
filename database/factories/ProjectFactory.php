<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(Project::TYPES),
            'client_id' => null,
            'status' => fake()->randomElement(Project::STATUSES),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Link the project to a client (a "lucrare" job).
     */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'type' => 'lucrare',
        ]);
    }
}
