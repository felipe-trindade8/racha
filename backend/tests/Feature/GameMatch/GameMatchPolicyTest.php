<?php

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets administrators perform every match action', function (string $ability): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();

    expect(Gate::forUser($admin)->allows($ability, $match))->toBeTrue();
})->with(['viewAny', 'view', 'create', 'update', 'delete']);

it('lets a player list matches', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', GameMatch::class))->toBeTrue();
});

it('lets a player view a match', function (): void {
    $user = User::factory()->create();
    $match = GameMatch::factory()->create();

    expect(Gate::forUser($user)->allows('view', $match))->toBeTrue();
});

it('forbids a player from creating, updating or deleting a match', function (): void {
    $user = User::factory()->create();
    $match = GameMatch::factory()->create();

    expect(Gate::forUser($user)->allows('create', GameMatch::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $match))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', $match))->toBeFalse();
});
