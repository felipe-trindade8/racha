<?php

namespace App\Services;

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use App\Models\TeamPlayer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encapsulates the match business rules: creating a match with its two teams
 * and rosters, and recording its score, each in a single transaction.
 *
 * On create, the two structural rules this service owns are that a match has
 * exactly two teams and that no player is rostered more than once; both are
 * checked before the transaction opens, so a rejected input never writes a
 * partial match. Field-level and state validation (team name presence, player
 * existence, the finished-match scoring guard) lives in the Form Requests that
 * drive the endpoints; this service trusts the shape it receives.
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
     * Record a match's score: set each team's result, apply any per-player
     * ratings and move the match to finished, all in one transaction.
     *
     * The finished-match guard (a finished match must be reopened before it can
     * be scored again) and the shape of the payload are enforced by the Form
     * Request, so this service trusts the data it receives.
     *
     * Expected shape:
     *   [
     *     'team_a_result'  => string,
     *     'team_b_result'  => string,
     *     'player_ratings' => [['team_player_id' => int, 'game_rating' => int], ...],
     *   ]
     *
     * @param  array<string, mixed>  $data
     */
    public function recordScore(GameMatch $match, array $data): GameMatch
    {
        return DB::transaction(function () use ($match, $data): GameMatch {
            $match->teamA?->update(['result' => $data['team_a_result']]);
            $match->teamB?->update(['result' => $data['team_b_result']]);

            foreach (Arr::get($data, 'player_ratings', []) as $rating) {
                TeamPlayer::whereKey($rating['team_player_id'])
                    ->update(['game_rating' => $rating['game_rating']]);
            }

            $match->update(['status' => GameMatchStatusEnum::Finished]);

            return $match->load('teams');
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
