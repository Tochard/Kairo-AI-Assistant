<?php

namespace Database\Factories;

use App\Enums\TrackableDirection;
use App\Models\Trackable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trackable>
 */
class TrackableFactory extends Factory
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
            'name' => fake()->randomElement(['Savings', 'Gym minutes', 'Water intake', 'Reading pages']),
            'unit' => fake()->randomElement(['₦', 'kg', 'hours', 'count', '%']),
            'direction' => TrackableDirection::UpGood,
            'target_value' => fake()->optional()->randomFloat(2, 10, 1000),
        ];
    }

    public function downGood(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => TrackableDirection::DownGood,
        ]);
    }
}
