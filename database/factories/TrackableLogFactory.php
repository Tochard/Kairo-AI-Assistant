<?php

namespace Database\Factories;

use App\Models\Trackable;
use App\Models\TrackableLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackableLog>
 */
class TrackableLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trackable_id' => Trackable::factory(),
            'value' => fake()->randomFloat(2, 1, 100),
            'note' => fake()->optional()->sentence(4),
            'logged_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
