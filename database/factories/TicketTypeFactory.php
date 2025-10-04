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
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(10, 200);
        $sold = $this->faker->numberBetween(0, $quantity);
        
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->randomElement(['regular', 'vip', 'premium']),
            'price' => $this->faker->randomFloat(2, 10, 300),
            'quantity' => $quantity,
            'available' => $quantity - $sold, // احسب المتاح بناءً على الكمية والمبيعات
            'description' => $this->faker->optional(0.7)->sentence(),
            'sold' => $sold,
        ];
    }

    // إصلاح الحالات الخاصة - إزالة أي ذكر لـ 'type'
    public function regular(): static
    {
        $quantity = $this->faker->numberBetween(50, 200);
        $sold = $this->faker->numberBetween(0, 50);
        
        return $this->state(fn (array $attributes) => [
            'name' => 'regular',
            'price' => $this->faker->randomFloat(2, 10, 50),
            'quantity' => $quantity,
            'available' => $quantity - $sold,
            'description' => 'تذكرة عادية مع دخول قياسي',
            'sold' => $sold,
        ]);
    }

    public function vip(): static
    {
        $quantity = $this->faker->numberBetween(20, 50);
        $sold = $this->faker->numberBetween(0, 20);
        
        return $this->state(fn (array $attributes) => [
            'name' => 'vip',
            'price' => $this->faker->randomFloat(2, 100, 200),
            'quantity' => $quantity,
            'available' => $quantity - $sold,
            'description' => 'تذكرة VIP مع مميزات خاصة',
            'sold' => $sold,
        ]);
    }

    public function premium(): static
    {
        $quantity = $this->faker->numberBetween(10, 30);
        $sold = $this->faker->numberBetween(0, 10);
        
        return $this->state(fn (array $attributes) => [
            'name' => 'premium',
            'price' => $this->faker->randomFloat(2, 200, 300),
            'quantity' => $quantity,
            'available' => $quantity - $sold,
            'description' => 'تذكرة بريميوم مع أفضل المقاعد',
            'sold' => $sold,
        ]);
    }
}