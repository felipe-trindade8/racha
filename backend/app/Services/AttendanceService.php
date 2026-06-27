<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Enums\GameMatchStatusEnum;
use App\Enums\PlayerStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Encapsulates the attendance business rules: confirming (or updating) a player's
 * attendance for a match.
 *
 * Confirming is an upsert — a player has at most one attendance record per match
 * (the unique `(player_id, game_match_id)` constraint), so confirming again
 * overwrites the prior status rather than adding a row. The two rules this
 * service owns are checked before the transaction opens, so a rejected input
 * never writes: a finished match cannot take attendance, and only an active
 * player may confirm. Both surface as a `422` through the global handler.
 */
class AttendanceService
{
    /**
     * The attendance columns selected by default when listing.
     *
     * Qualified with the table name because the listing query joins `players`
     * to order by name; `player_id` must be present so the player relationship
     * can be eager-loaded.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = [
        'attendances.id',
        'attendances.player_id',
        'attendances.game_match_id',
        'attendances.status',
        'attendances.confirmed',
        'attendances.created_at',
        'attendances.updated_at',
    ];

    /**
     * Return a paginated list of a match's attendance records with each player
     * eager-loaded, ordered by player name.
     *
     * The `players` join is used only for the ordering; the selected columns are
     * qualified to `attendances` so the hydrated models stay attendance records.
     *
     * @param  list<string>  $columns
     * @return LengthAwarePaginator<int, Attendance>
     */
    public function paginate(GameMatch $match, int $perPage = 15, array $columns = self::DEFAULT_COLUMNS): LengthAwarePaginator
    {
        return $match->attendances()
            ->join('players', 'players.id', '=', 'attendances.player_id')
            ->with('player')
            ->orderBy('players.name')
            ->orderBy('attendances.id')
            ->paginate($perPage, $columns);
    }

    /**
     * Confirm (or update) a player's attendance for a match.
     *
     * Always marks the record confirmed; the status reflects the player's
     * availability (available / injured / missing) for this match.
     *
     * @throws ValidationException when the match is finished or the player is
     *                             not active.
     */
    public function confirm(Player $player, GameMatch $match, AttendanceStatusEnum $status): Attendance
    {
        $this->ensureMatchIsOpen($match);
        $this->ensurePlayerIsActive($player);

        return DB::transaction(fn (): Attendance => Attendance::updateOrCreate(
            ['player_id' => $player->id, 'game_match_id' => $match->id],
            ['status' => $status, 'confirmed' => true],
        ));
    }

    /**
     * A finished match is settled and no longer takes attendance.
     *
     * @throws ValidationException
     */
    private function ensureMatchIsOpen(GameMatch $match): void
    {
        if ($match->status === GameMatchStatusEnum::Finished) {
            throw ValidationException::withMessages([
                'game_match_id' => 'Attendance cannot be confirmed for a finished match.',
            ]);
        }
    }

    /**
     * Only an active player may confirm attendance.
     *
     * @throws ValidationException
     */
    private function ensurePlayerIsActive(Player $player): void
    {
        if ($player->status !== PlayerStatusEnum::Active) {
            throw ValidationException::withMessages([
                'player_id' => 'Only active players can confirm attendance.',
            ]);
        }
    }
}
