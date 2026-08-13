<?php

namespace Database\Factories;

use App\Models\Commitment;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commitment>
 */
class CommitmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'locked_payload' => [
                'checkin_cadence' => 'weekly',
                'task_count' => 2,
            ],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'renegotiation_count' => 0,
        ];
    }
}
