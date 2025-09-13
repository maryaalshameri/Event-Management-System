<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->attendee(),
            'event_id' => Event::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional(0.7)->paragraph(), // 70% chance of having comment
        ];
    }

    // حالات خاصة للتقييمات
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }

    public function withComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => $this->faker->paragraph(),
        ]);
    }

    public function withoutComment(): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => null,
        ]);
    }
}
