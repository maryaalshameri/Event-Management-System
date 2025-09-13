<?php

namespace Database\Factories;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 months');

        return [
            'organizer_id' => User::factory()->organizer(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'capacity' => $this->faker->numberBetween(50, 500),
            'category' => $this->faker->randomElement(['concert', 'conference', 'workshop', 'sports', 'festival']),
            'available_seats' => fn (array $attributes) => $attributes['capacity'],
            'price' => $this->faker->randomFloat(2, 0, 500),
            'image' => $this->faker->imageUrl(640, 480, 'events'),
            'status' => $this->faker->randomElement(['draft', 'published', 'cancelled', 'completed']),
        ];
    }

    // حالات خاصة للحالة
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
