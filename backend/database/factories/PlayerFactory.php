<?php

namespace Database\Factories;

use App\Enums\PlayerStatusEnum;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nickname' => fake()->firstName(),
            'rating' => fake()->numberBetween(1, 5),
            'status' => PlayerStatusEnum::Active,
            'phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * Indicate that the player is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlayerStatusEnum::Inactive,
        ]);
    }

    /**
     * Indicate that the player is injured.
     */
    public function injured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlayerStatusEnum::Injured,
        ]);
    }
}
