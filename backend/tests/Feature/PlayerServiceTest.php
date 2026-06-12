<?php

use App\Enums\PlayerStatusEnum;
use App\Models\Player;
use App\Models\Position;
use App\Services\PlayerService;
use Database\Seeders\PositionSeeder;

beforeEach(function (): void {
    $this->seed(PositionSeeder::class);
    $this->service = app(PlayerService::class);
});

it('creates a player with positions in a single call', function (): void {
    $positionIds = Position::whereIn('code', ['GK', 'DEF'])->pluck('id')->all();

    $player = $this->service->create([
        'name' => 'Cafu',
        'nickname' => 'Cafu',
        'rating' => 5,
        'status' => PlayerStatusEnum::Active->value,
        'phone' => '+55 11 99999-0000',
        'position_ids' => $positionIds,
    ]);

    expect($player->exists)->toBeTrue()
        ->and($player->name)->toBe('Cafu')
        ->and($player->positions->pluck('code')->sort()->values()->all())
        ->toBe(['DEF', 'GK']);
});

it('creates a player without touching positions when none are provided', function (): void {
    $player = $this->service->create([
        'name' => 'Rivaldo',
        'rating' => 4,
        'status' => PlayerStatusEnum::Active->value,
    ]);

    expect($player->positions)->toHaveCount(0);
});

it('updates a player attributes', function (): void {
    $player = Player::factory()->create(['rating' => 2]);

    $updated = $this->service->update($player, [
        'rating' => 5,
        'status' => PlayerStatusEnum::Injured->value,
    ]);

    expect($updated->rating)->toBe(5)
        ->and($updated->status)->toBe(PlayerStatusEnum::Injured);
});

it('re-syncs positions on update', function (): void {
    $player = Player::factory()->create();
    $player->positions()->attach(Position::where('code', 'GK')->value('id'));

    $newIds = Position::whereIn('code', ['MID', 'FWD'])->pluck('id')->all();

    $this->service->update($player, ['position_ids' => $newIds]);

    expect($player->refresh()->positions->pluck('code')->sort()->values()->all())
        ->toBe(['FWD', 'MID']);
});

it('leaves positions untouched when position_ids is omitted on update', function (): void {
    $player = Player::factory()->create();
    $player->positions()->attach(Position::where('code', 'GK')->value('id'));

    $this->service->update($player, ['rating' => 3]);

    expect($player->refresh()->positions->pluck('code')->all())->toBe(['GK']);
});

it('clears positions when an empty position_ids array is provided', function (): void {
    $player = Player::factory()->create();
    $player->positions()->attach(Position::where('code', 'GK')->value('id'));

    $this->service->update($player, ['position_ids' => []]);

    expect($player->refresh()->positions)->toHaveCount(0);
});

it('transitions a player to a new status', function (): void {
    $player = Player::factory()->create(['status' => PlayerStatusEnum::Active]);

    $updated = $this->service->updateStatus($player, PlayerStatusEnum::Inactive);

    expect($updated->status)->toBe(PlayerStatusEnum::Inactive)
        ->and($player->fresh()->status)->toBe(PlayerStatusEnum::Inactive);
});

it('excludes inactive players from the default pagination', function (): void {
    Player::factory()->count(2)->create();
    Player::factory()->injured()->create();
    Player::factory()->inactive()->create();

    $players = $this->service->paginate();

    expect($players->total())->toBe(3)
        ->and($players->pluck('status'))->not->toContain(PlayerStatusEnum::Inactive);
});

it('filters the pagination by status', function (): void {
    Player::factory()->count(2)->create();
    Player::factory()->inactive()->create();

    $players = $this->service->paginate(status: PlayerStatusEnum::Inactive);

    expect($players->total())->toBe(1)
        ->and($players->first()->status)->toBe(PlayerStatusEnum::Inactive);
});
