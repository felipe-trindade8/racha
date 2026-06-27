<?php

namespace Database\Factories;

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'game_match_id' => GameMatch::factory(),
            'status' => AttendanceStatusEnum::Available,
            'confirmed' => false,
        ];
    }

    /**
     * Indicate that the player is injured for the match.
     */
    public function injured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Injured,
        ]);
    }

    /**
     * Indicate that the player cannot attend the match.
     */
    public function missing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatusEnum::Missing,
        ]);
    }

    /**
     * Indicate that the player has confirmed their presence.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed' => true,
        ]);
    }

    /**
     * Record the attendance for the given player.
     */
    public function forPlayer(Player $player): static
    {
        return $this->state(fn (array $attributes) => [
            'player_id' => $player->id,
        ]);
    }

    /**
     * Record the attendance for the given match.
     */
    public function forMatch(GameMatch $match): static
    {
        return $this->state(fn (array $attributes) => [
            'game_match_id' => $match->id,
        ]);
    }
}
