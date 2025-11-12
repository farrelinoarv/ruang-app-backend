<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::inRandomOrder()->first()->id ?? Campaign::factory(),
            'user_id' => rand(0, 1) ? User::inRandomOrder()->first()->id : null,
            'donor_name' => fake()->name(),
            'amount' => fake()->numberBetween(10000, 1000000),
            'message' => fake()->sentence(),
            'payment_method' => 'midtrans',
            'midtrans_order_id' => fake()->uuid(),
            'midtrans_transaction_id' => fake()->uuid(),
            'payment_status' => 'success',
        ];
    }
}
