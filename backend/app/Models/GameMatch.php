<?php

namespace App\Models;

use App\Enums\GameMatchStatusEnum;
use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['date', 'team_a_id', 'team_b_id', 'status'])]
class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => GameMatchStatusEnum::class,
        ];
    }

    /**
     * The two teams that play this match.
     *
     * Exactly two are enforced at the service layer; the data layer only
     * models the relationship.
     *
     * @return HasMany<GameMatchTeam, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(GameMatchTeam::class);
    }

    /**
     * The first (home) team of this match.
     *
     * @return BelongsTo<GameMatchTeam, $this>
     */
    public function teamA(): BelongsTo
    {
        return $this->belongsTo(GameMatchTeam::class, 'team_a_id');
    }

    /**
     * The second (away) team of this match.
     *
     * @return BelongsTo<GameMatchTeam, $this>
     */
    public function teamB(): BelongsTo
    {
        return $this->belongsTo(GameMatchTeam::class, 'team_b_id');
    }
}
