<?php

use App\Models\Player;
use App\Models\Position;
use Database\Seeders\PositionSeeder;

it('seeds the four canonical positions', function (): void {
    $this->seed(PositionSeeder::class);

    expect(Position::pluck('code')->sort()->values()->all())
        ->toBe(['DEF', 'FWD', 'GK', 'MID']);
});

it('seeds canonical positions idempotently', function (): void {
    $this->seed(PositionSeeder::class);
    $this->seed(PositionSeeder::class);

    expect(Position::count())->toBe(4);
});

it('exposes the players that hold a position', function (): void {
    $this->seed(PositionSeeder::class);

    $goalkeeper = Position::where('code', 'GK')->firstOrFail();
    $player = Player::factory()->create();
    $player->positions()->attach($goalkeeper);

    expect($goalkeeper->players)->toHaveCount(1)
        ->and($goalkeeper->players->first()->is($player))->toBeTrue();
});
