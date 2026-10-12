<?php

namespace Database\Factories;

use App\Models\AgendaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaItem>
 */
class AgendaItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'text' => fake()->paragraph(),
            'type' => fake()->randomElement(AgendaItem::TYPES),
            'status' => fake()->randomElement(['inbox', 'de_facut']),
            'priority' => fake()->randomElement(AgendaItem::PRIORITIES),
            'due_at' => null,
            'reminder_at' => null,
            'repeat' => null,
            'project_id' => null,
            'pinned_at' => null,
            'completed_at' => null,
        ];
    }
}
