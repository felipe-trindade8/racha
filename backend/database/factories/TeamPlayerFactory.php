<?php

namespace Database\Factories;

use App\Models\GameMatchTeam;
use App\Models\Player;
use App\Models\TeamPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamPlayer>
 */
class TeamPlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_match_team_id' => GameMatchTeam::factory(),
            'player_id' => Player::factory(),
            'position' => fake()->randomElement(['GK', 'DEF', 'MID', 'FWD']),
            'game_rating' => null,
            'is_starter' => false,
        ];
    }

    /**
     * Indicate that the player starts the match.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_starter' => true,
        ]);
    }

    /**
     * Roster the player on the given team.
     */
    public function forTeam(GameMatchTeam $team): static
    {
        return $this->state(fn (array $attributes) => [
            'game_match_team_id' => $team->id,
        ]);
    }
}
