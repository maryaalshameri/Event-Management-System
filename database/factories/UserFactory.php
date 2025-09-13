<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // password
            'remember_token' => Str::random(10),
            'role' => $this->faker->randomElement(['admin', 'organizer', 'attendee']),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'avatar' => $this->faker->imageUrl(100, 100, 'people'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function organizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'organizer',
        ]);
    }

    public function attendee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'attendee',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
 
}
