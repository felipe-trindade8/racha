<?php

namespace App\Models;

use Database\Factories\TeamPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['game_match_team_id', 'player_id', 'position_id', 'game_rating', 'is_starter'])]
class TeamPlayer extends Model
{
    /** @use HasFactory<TeamPlayerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_starter' => 'boolean',
        ];
    }

    /**
     * The team this player is rostered on for the match.
     *
     * @return BelongsTo<GameMatchTeam, $this>
     */
    public function gameMatchTeam(): BelongsTo
    {
        return $this->belongsTo(GameMatchTeam::class);
    }

    /**
     * The player on the roster.
     *
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * The position the player lines up in for this match.
     *
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
