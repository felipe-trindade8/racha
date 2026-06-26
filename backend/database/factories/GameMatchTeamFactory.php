<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatchTeam>
 */
class GameMatchTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_match_id' => GameMatch::factory(),
            'team_name' => fake()->randomElement(['Team A', 'Team B']),
            'result' => null,
        ];
    }

    /**
     * Associate the team with the given match.
     */
    public function forMatch(GameMatch $match): static
    {
        return $this->state(fn (array $attributes) => [
            'game_match_id' => $match->id,
        ]);
    }
}
