<?php

namespace Database\Factories;
use App\Models\Event;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class TicketTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => $this->faker->randomElement(['regular', 'vip', 'premium']),
            'price' => $this->faker->randomFloat(2, 10, 300),
            'quantity' => $this->faker->numberBetween(10, 200),
            'sold' => $this->faker->numberBetween(0, 100),
        ];
    }

    // حالات خاصة لأنواع التذاكر
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'regular',
            'price' => $this->faker->randomFloat(2, 10, 50),
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vip',
            'price' => $this->faker->randomFloat(2, 100, 200),
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'premium',
            'price' => $this->faker->randomFloat(2, 200, 300),
        ]);
    }
}
