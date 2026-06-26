<?php

namespace App\Models;

use Database\Factories\GameMatchTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['game_match_id', 'team_name', 'result'])]
class GameMatchTeam extends Model
{
    /** @use HasFactory<GameMatchTeamFactory> */
    use HasFactory;

    /**
     * The match this team belongs to.
     *
     * @return BelongsTo<GameMatch, $this>
     */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }
}
