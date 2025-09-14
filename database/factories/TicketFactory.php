<?php

namespace Database\Factories;
use App\Models\OrderItem;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
 public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'pdf_path' => $this->faker->filePath(),
            'qr_path' => $this->faker->filePath(),
            'used_at' => $this->faker->optional(0.3)->dateTime(), // 30% chance of being used
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => $this->faker->dateTime(),
        ]);
    }

    public function notUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => null,
        ]);
    }
}
