<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'goal_id' => null,
            'title' => fake()->sentence(3),
            'due_date' => fake()->optional()->dateTimeBetween('-1 week', '+2 weeks'),
            'recurrence_rule' => null,
            'status' => TaskStatus::Pending,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
        ]);
    }

    public function missed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Missed,
            'due_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }
}
