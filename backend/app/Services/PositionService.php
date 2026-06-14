<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

/**
 * Encapsulates the position reference data access, keeping the controller thin
 * and free of any direct database queries.
 */
class PositionService
{
    /**
     * The position columns selected by default when listing.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = ['id', 'code', 'name'];

    /**
     * Return the full, ordered list of positions a player can hold.
     *
     * @param  list<string>  $columns
     * @return Collection<int, Position>
     */
    public function list(array $columns = self::DEFAULT_COLUMNS): Collection
    {
        return Position::query()
            ->orderBy('id')
            ->get($columns);
    }
}
