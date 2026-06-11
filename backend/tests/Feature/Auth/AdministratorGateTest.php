<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('grants the administrator ability to administrators only', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = User::factory()->create();

    expect(Gate::forUser($admin)->allows('administrator'))->toBeTrue()
        ->and(Gate::forUser($player)->allows('administrator'))->toBeFalse();
});

it('lets administrators bypass an ability that otherwise denies everyone', function (): void {
    // A policy/gate that only encodes the player (non-admin) rule, here a flat
    // deny. The global Gate::before should still let administrators through.
    Gate::define('player-restricted-feature', fn (User $user): bool => false);

    $admin = User::factory()->administrator()->create();
    $player = User::factory()->create();

    expect(Gate::forUser($admin)->allows('player-restricted-feature'))->toBeTrue()
        ->and(Gate::forUser($player)->allows('player-restricted-feature'))->toBeFalse();
});
