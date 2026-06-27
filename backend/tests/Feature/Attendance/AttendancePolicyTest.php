<?php

use App\Models\Attendance;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets every role view the attendance list', function (): void {
    $admin = User::factory()->administrator()->create();
    $linked = Player::factory()->create();
    $player = User::factory()->create(['player_id' => $linked->id]);
    $unlinked = User::factory()->create(['player_id' => null]);

    expect(Gate::forUser($admin)->allows('viewAny', Attendance::class))->toBeTrue()
        ->and(Gate::forUser($player)->allows('viewAny', Attendance::class))->toBeTrue()
        ->and(Gate::forUser($unlinked)->allows('viewAny', Attendance::class))->toBeTrue();
});

it('lets a player confirm their own attendance', function (): void {
    $own = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    expect(Gate::forUser($user)->allows('confirm', [Attendance::class, $own]))->toBeTrue();
});

it('forbids a player from confirming another player attendance', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    expect(Gate::forUser($user)->allows('confirm', [Attendance::class, $other]))->toBeFalse();
});

it('forbids a user with no linked player from confirming any attendance', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => null]);

    expect(Gate::forUser($user)->allows('confirm', [Attendance::class, $player]))->toBeFalse();
});

it('lets an administrator confirm or override attendance for any player', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();

    expect(Gate::forUser($admin)->allows('confirm', [Attendance::class, $player]))->toBeTrue();
});
