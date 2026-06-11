<?php

use App\Models\Player;
use App\Models\Position;
use Database\Seeders\PositionSeeder;

beforeEach(function (): void {
    $this->seed(PositionSeeder::class);
});

it('attaches multiple positions to a player', function (): void {
    $player = Player::factory()->create();
    $positions = Position::whereIn('code', ['DEF', 'MID'])->get();

    $player->positions()->attach($positions->pluck('id'));

    expect($player->positions)->toHaveCount(2)
        ->and($player->positions->pluck('code')->sort()->values()->all())
        ->toBe(['DEF', 'MID']);
});

it('detaches a position from a player', function (): void {
    $player = Player::factory()->create();
    $positions = Position::whereIn('code', ['DEF', 'MID'])->get();
    $player->positions()->attach($positions->pluck('id'));

    $player->positions()->detach(
        Position::where('code', 'DEF')->value('id'),
    );

    expect($player->refresh()->positions->pluck('code')->all())->toBe(['MID']);
});

it('does not duplicate a position already attached to a player', function (): void {
    $player = Player::factory()->create();
    $midfielder = Position::where('code', 'MID')->firstOrFail();

    $player->positions()->syncWithoutDetaching($midfielder);
    $player->positions()->syncWithoutDetaching($midfielder);

    expect($player->positions)->toHaveCount(1);
});
