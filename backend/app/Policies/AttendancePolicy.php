<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Authorization rules for the Attendance resource.
 *
 * Administrators bypass every method through the global Gate::before hook in
 * AppServiceProvider, so these methods only encode the non-administrator
 * (player) rules: any role may view the attendance list, and a player may
 * confirm only the attendance of the record linked to their own user via
 * users.player_id.
 */
class AttendancePolicy
{
    /**
     * Determine whether the user can view the attendance list.
     *
     * Attendance is visible to every role.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can confirm attendance for the given player.
     *
     * A player may confirm only their own attendance; administrators manage any
     * player's attendance through the global bypass.
     */
    public function confirm(User $user, Player $player): bool
    {
        return $user->player_id === $player->id;
    }
}
