<?php

namespace Database\Factories;

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'team_a_id' => null,
            'team_b_id' => null,
            'status' => GameMatchStatusEnum::Planned,
        ];
    }

    /**
     * Indicate that the match has been finished.
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameMatchStatusEnum::Finished,
        ]);
    }

    /**
     * Create the match's two teams and assign them as team A and team B.
     */
    public function withTeams(): static
    {
        return $this->afterCreating(function (GameMatch $match): void {
            $teamA = GameMatchTeam::factory()->forMatch($match)->create();
            $teamB = GameMatchTeam::factory()->forMatch($match)->create();

            $match->update([
                'team_a_id' => $teamA->id,
                'team_b_id' => $teamB->id,
            ]);
        });
    }
}
