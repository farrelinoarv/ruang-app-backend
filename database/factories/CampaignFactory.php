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
        $status = $this->faker->randomElement(['pending', 'approved', 'rejected', 'edit_pending', 'closed']);

        // Get random user
        $user = User::inRandomOrder()->first();

    // If status is NOT pending, verify the user as civitas
    if ($user && $status !== 'pending') {
            $user->update(['is_verified_civitas' => true]);
        }

        return [
            'user_id' => $user->id ?? 1,
            'category_id' => Category::inRandomOrder()->value('id') ?? 1,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'description' => $this->faker->paragraph(),
            'target_amount' => $this->faker->numberBetween(1000000, 5000000),
            'collected_amount' => $status === 'approved' ? $this->faker->numberBetween(0, 1000000) : 0,
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+3 month'),
            'status' => $status,
            'cover_image' => 'default.jpg',
        ];
    }
}
