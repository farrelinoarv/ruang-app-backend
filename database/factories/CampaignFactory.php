<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Campaign::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'user_id' => User::inRandomOrder()->value('id') ?? 1,
            'category_id' => Category::inRandomOrder()->value('id') ?? 1,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'description' => $this->faker->paragraph(),
            'target_amount' => $this->faker->numberBetween(1000000, 5000000),
            'collected_amount' => $this->faker->numberBetween(0, 1000000),
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 month'),
            'status' => 'approved',
            'cover_image' => 'default.jpg',
        ];
    }
}
