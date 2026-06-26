<?php

namespace App\Services;

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use App\Models\TeamPlayer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encapsulates the match business rules: listing matches, creating one with its
 * two teams and rosters, updating it and recording its score. Each write runs in
 * a single transaction.
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
     * The match columns selected by default when listing.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = ['id', 'date', 'team_a_id', 'team_b_id', 'status', 'created_at', 'updated_at'];

    /**
     * Return a paginated list of matches with their teams eager-loaded, newest
     * first.
     *
     * Each filter is optional and only narrows the result when provided: `status`
     * matches exactly and `date` (a `Y-m-d` string) restricts the list to matches
     * played on that day.
     *
     * @param  list<string>  $columns
     * @return LengthAwarePaginator<int, GameMatch>
     */
    public function paginate(int $perPage = 15, ?GameMatchStatusEnum $status = null, ?string $date = null, array $columns = self::DEFAULT_COLUMNS): LengthAwarePaginator
    {
        return GameMatch::query()
            ->with('teams')
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($date !== null && $date !== '', fn ($query) => $query->whereDate('date', $date))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage, $columns);
    }

    /**
     * Return a paginated history of finished matches with both teams' results,
     * newest first.
     *
     * Each team's result is pulled in a single query by joining the teams table
     * twice (one alias per side). The inner joins also require both teams to be
     * present, so only fully-formed finished matches appear.
     *
     * @return LengthAwarePaginator<int, GameMatch>
     */
    public function history(int $perPage = 15): LengthAwarePaginator
    {
        return GameMatch::query()
            ->join('game_match_teams as team_a', 'team_a.id', '=', 'game_matches.team_a_id')
            ->join('game_match_teams as team_b', 'team_b.id', '=', 'game_matches.team_b_id')
            ->where('game_matches.status', GameMatchStatusEnum::Finished)
            ->orderByDesc('game_matches.date')
            ->orderByDesc('game_matches.id')
            ->paginate($perPage, [
                'game_matches.id',
                'game_matches.date',
                'game_matches.status',
                'game_matches.team_a_id',
                'game_matches.team_b_id',
                'team_a.team_name as team_a_name',
                'team_a.result as team_a_result',
                'team_b.team_name as team_b_name',
                'team_b.result as team_b_result',
            ]);
    }

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
     * Update a match's own attributes.
     *
     * Only the match date is editable here: status is transitioned through the
     * dedicated score endpoint and the team/roster composition is fixed at
     * creation, so neither is touched by the generic update.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(GameMatch $match, array $data): GameMatch
    {
        return DB::transaction(function () use ($match, $data): GameMatch {
            $match->update(Arr::only($data, ['date']));

            return $match;
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
