<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Enums\GameMatchStatusEnum;
use App\Enums\PlayerStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
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
