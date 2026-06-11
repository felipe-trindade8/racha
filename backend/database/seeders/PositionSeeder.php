<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * The canonical positions a player can hold.
     *
     * @var list<array{code: string, name: string}>
     */
    private const POSITIONS = [
        ['code' => 'GK', 'name' => 'Goalkeeper'],
        ['code' => 'DEF', 'name' => 'Defender'],
        ['code' => 'MID', 'name' => 'Midfielder'],
        ['code' => 'FWD', 'name' => 'Forward'],
    ];

    /**
     * Seed the canonical positions.
     */
    public function run(): void
    {
        foreach (self::POSITIONS as $position) {
            Position::updateOrCreate(
                ['code' => $position['code']],
                ['name' => $position['name']],
            );
        }
    }
}
