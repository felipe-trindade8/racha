<?php

use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets administrators perform every player action', function (string $ability): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();

    expect(Gate::forUser($admin)->allows($ability, $player))->toBeTrue();
})->with(['viewAny', 'view', 'create', 'update', 'delete', 'updateStatus']);

it('lets a player view their own linked record', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    expect(Gate::forUser($user)->allows('view', $player))->toBeTrue();
});

it('lets a player update their own linked record', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    expect(Gate::forUser($user)->allows('update', $player))->toBeTrue();
});

it('forbids a player from viewing another player record', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    expect(Gate::forUser($user)->allows('view', $other))->toBeFalse();
});

it('forbids a player from updating another player record', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    expect(Gate::forUser($user)->allows('update', $other))->toBeFalse();
});

it('forbids a player with no linked record from viewing or updating any player', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => null]);

    expect(Gate::forUser($user)->allows('view', $player))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $player))->toBeFalse();
});

it('forbids a player from listing, creating or deleting players', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    expect(Gate::forUser($user)->allows('viewAny', Player::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', Player::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $player))->toBeFalse();
});

it('forbids a player from changing the status of their own linked record', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    expect(Gate::forUser($user)->allows('updateStatus', $player))->toBeFalse();
});
