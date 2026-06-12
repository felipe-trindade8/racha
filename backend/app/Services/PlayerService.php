<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates the player create/update business rules, including the
 * synchronisation of the many-to-many positions relationship.
 *
 * Keeping this logic here lets the controllers stay thin and free of any
 * database access.
 */
class PlayerService
{
    /**
     * The player columns selected by default when listing.
     *
     * `id` must be present so the positions relationship can be eager-loaded.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = ['id', 'name', 'nickname', 'rating', 'status', 'phone', 'created_at', 'updated_at'];

    /**
     * Return a paginated list of players with their positions eager-loaded.
     *
     * @param  list<string>  $columns
     * @return LengthAwarePaginator<int, Player>
     */
    public function paginate(int $perPage = 15, array $columns = self::DEFAULT_COLUMNS): LengthAwarePaginator
    {
        return Player::query()
            ->with('positions')
            ->paginate($perPage, $columns);
    }

    /**
     * Create a player and sync its positions in a single call.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Player
    {
        return DB::transaction(function () use ($data): Player {
            $player = Player::create(Arr::except($data, ['position_ids']));

            if (array_key_exists('position_ids', $data)) {
                $player->positions()->sync($data['position_ids'] ?? []);
            }

            return $player;
        });
    }

    /**
     * Update a player and re-sync its positions when provided.
     *
     * Positions are only touched when the `position_ids` key is present, so a
     * partial update never wipes existing positions by accident.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Player $player, array $data): Player
    {
        return DB::transaction(function () use ($player, $data): Player {
            $player->update(Arr::except($data, ['position_ids']));

            if (array_key_exists('position_ids', $data)) {
                $player->positions()->sync($data['position_ids'] ?? []);
            }

            return $player;
        });
    }
}
