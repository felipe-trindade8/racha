<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Authorization rules for the Player resource.
 *
 * Administrators bypass every method through the global Gate::before hook in
 * AppServiceProvider, so these methods only encode the non-administrator
 * (player) rules: a player may view or update only the record linked to their
 * own user via users.player_id, and may not list, create or delete players.
 */
class PlayerPolicy
{
    /**
     * Determine whether the user can list players.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the player.
     */
    public function view(User $user, Player $player): bool
    {
        return $user->player_id === $player->id;
    }

    /**
     * Determine whether the user can create players.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the player.
     */
    public function update(User $user, Player $player): bool
    {
        return $user->player_id === $player->id;
    }

    /**
     * Determine whether the user can delete the player.
     */
    public function delete(User $user, Player $player): bool
    {
        return false;
    }
}
