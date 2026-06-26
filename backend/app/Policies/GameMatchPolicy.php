<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

/**
 * Authorization rules for the GameMatch resource.
 *
 * Match management is administrator-only; players have read access. Administrators
 * bypass every method through the global Gate::before hook in AppServiceProvider,
 * so these methods only encode the non-administrator (player) rules: a player may
 * list and view matches but may not create, update or delete them.
 */
class GameMatchPolicy
{
    /**
     * Determine whether the user can list matches.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the match.
     */
    public function view(User $user, GameMatch $match): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create matches.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the match.
     */
    public function update(User $user, GameMatch $match): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the match.
     */
    public function delete(User $user, GameMatch $match): bool
    {
        return false;
    }
}
