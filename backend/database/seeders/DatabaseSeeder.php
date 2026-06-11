<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->administrator()->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
        ]);

        $this->call(PositionSeeder::class);

        $positions = Position::all();

        Player::factory()
            ->count(20)
            ->create()
            ->each(function (Player $player) use ($positions): void {
                $player->positions()->attach(
                    $positions->random(rand(1, 2))->pluck('id'),
                );
            });
    }
}
