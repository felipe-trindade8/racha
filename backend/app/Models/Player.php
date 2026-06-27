<?php

namespace App\Models;

use App\Enums\PlayerStatusEnum;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[Fillable(['name', 'nickname', 'rating', 'status', 'phone'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlayerStatusEnum::class,
        ];
    }

    /**
     * Constrain the rating to the 1-5 range at the model layer.
     *
     * @return Attribute<int, int>
     */
    protected function rating(): Attribute
    {
        return Attribute::make(
            set: function (int $value): int {
                if ($value < 1 || $value > 5) {
                    throw new InvalidArgumentException('The rating must be between 1 and 5.');
                }

                return $value;
            },
        );
    }

    /**
     * The positions this player can play.
     *
     * @return BelongsToMany<Position, $this>
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'player_positions');
    }

    /**
     * The financial transactions associated with this player.
     *
     * @return HasMany<FinancialTransaction, $this>
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * The attendance records of this player across matches.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
