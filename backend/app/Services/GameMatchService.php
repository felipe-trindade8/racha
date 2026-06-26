<?php

namespace App\Services;

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encapsulates the match creation business rules: persisting a match together
 * with its two teams and their roster assignments in a single transaction.
 *
 * The two structural rules this service owns are that a match has exactly two
 * teams and that no player is rostered more than once. Both are checked before
 * the transaction opens, so a rejected input never writes a partial match.
 * Field-level validation (presence of a team name, existence of players) lives
 * in the Form Request that drives the endpoint; this service trusts the shape
 * it receives.
 */
class GameMatchService
{
    /**
     * Create a match with its two teams and their rosters.
     *
     * Expected shape:
     *   [
     *     'date'  => 'Y-m-d',
     *     'teams' => [
     *       ['team_name' => string, 'players' => [
     *         ['player_id' => int, 'position_id' => ?int, 'is_starter' => ?bool],
     *         ...
     *       ]],
     *       ['team_name' => string, 'players' => [...]],
     *     ],
     *   ]
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when there are not exactly two teams or a
     *                             player is rostered more than once.
     */
    public function create(array $data): GameMatch
    {
        $teams = Arr::get($data, 'teams', []);

        $this->validate($teams);

        return DB::transaction(function () use ($data, $teams): GameMatch {
            $match = GameMatch::create([
                'date' => $data['date'],
                'status' => GameMatchStatusEnum::Planned,
            ]);

            [$teamA, $teamB] = array_map(
                fn (array $team): GameMatchTeam => $this->createTeam($match, $team),
                $teams,
            );

            $match->update([
                'team_a_id' => $teamA->id,
                'team_b_id' => $teamB->id,
            ]);

            return $match->load('teams.teamPlayers');
        });
    }

    /**
     * Enforce the two structural rules: exactly two teams and each player
     * rostered at most once across the whole match.
     *
     * @param  array<int, array<string, mixed>>  $teams
     *
     * @throws ValidationException
     */
    private function validate(array $teams): void
    {
        if (count($teams) !== 2) {
            throw ValidationException::withMessages([
                'teams' => 'A match must have exactly two teams.',
            ]);
        }

        $playerIds = collect($teams)
            ->flatMap(fn (array $team): array => Arr::pluck(Arr::get($team, 'players', []), 'player_id'));

        if ($playerIds->count() !== $playerIds->unique()->count()) {
            throw ValidationException::withMessages([
                'players' => 'A player cannot be rostered more than once in a match.',
            ]);
        }
    }

    /**
     * Create one team and its rostered players for the given match.
     *
     * @param  array<string, mixed>  $team
     */
    private function createTeam(GameMatch $match, array $team): GameMatchTeam
    {
        $gameMatchTeam = GameMatchTeam::create([
            'game_match_id' => $match->id,
            'team_name' => $team['team_name'],
        ]);

        foreach (Arr::get($team, 'players', []) as $player) {
            $gameMatchTeam->teamPlayers()->create([
                'player_id' => $player['player_id'],
                'position_id' => Arr::get($player, 'position_id'),
                'is_starter' => Arr::get($player, 'is_starter', false),
            ]);
        }

        return $gameMatchTeam;
    }
}
