<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'data' => json_encode(['example' => 'payload']),
            'is_read' => fake()->boolean(40),
            'type' => fake()->randomElement(['campaign', 'donation', 'system']),
        ];
    }
}
