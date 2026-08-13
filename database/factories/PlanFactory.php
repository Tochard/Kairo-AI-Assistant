<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Models\Goal;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
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
            'goal_id' => Goal::factory(),
            'draft_payload' => [
                'goal_title' => fake()->sentence(4),
                'goal_description' => fake()->paragraph(),
                'target_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
                'tasks' => [
                    ['title' => fake()->sentence(3)],
                    ['title' => fake()->sentence(3)],
                ],
                'checkin_cadence' => 'weekly',
            ],
            'status' => PlanStatus::Approved,
            'approved_at' => now(),
        ];
    }
}
