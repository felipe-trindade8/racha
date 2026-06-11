<?php

use App\Enums\PlayerStatusEnum;
use App\Models\Player;

it('defaults a factory player to the active status', function (): void {
    $player = Player::factory()->create();

    expect($player->status)->toBe(PlayerStatusEnum::Active);
});

it('defaults the status to active at the database level when none is provided', function (): void {
    $player = Player::create([
        'name' => 'Pelé',
        'rating' => 5,
    ]);

    expect($player->fresh()->status)->toBe(PlayerStatusEnum::Active);
});

it('casts the status attribute to the PlayerStatusEnum', function (): void {
    $player = Player::factory()->create();

    expect($player->status)->toBeInstanceOf(PlayerStatusEnum::class);
});

it('accepts a rating within the 1 to 5 range', function (int $rating): void {
    $player = Player::factory()->create(['rating' => $rating]);

    expect($player->rating)->toBe($rating);
})->with([1, 2, 3, 4, 5]);

it('rejects a rating outside the 1 to 5 range', function (int $rating): void {
    expect(fn () => Player::factory()->make(['rating' => $rating]))
        ->toThrow(InvalidArgumentException::class);
})->with([0, 6, -1, 10]);

it('creates an inactive player through the factory state', function (): void {
    $player = Player::factory()->inactive()->create();

    expect($player->status)->toBe(PlayerStatusEnum::Inactive);
});

it('creates an injured player through the factory state', function (): void {
    $player = Player::factory()->injured()->create();

    expect($player->status)->toBe(PlayerStatusEnum::Injured);
});
