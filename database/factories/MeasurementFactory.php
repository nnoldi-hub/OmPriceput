<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true).' - loc de masurat',
            'location' => fake()->address(),
            'client_id' => null,
            'project_id' => null,
            'status' => 'in_progress',
            'notes' => null,
            'measured_at' => now(),
        ];
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn () => ['client_id' => $client->id]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => ['project_id' => $project->id]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
