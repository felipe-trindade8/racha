<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('defaults a factory user to the player role with no player_id', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::Player)
        ->and($user->player_id)->toBeNull();
});

it('casts the role attribute to the Role enum', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBeInstanceOf(Role::class);
});

it('hashes the password instead of storing it in plain text', function (): void {
    $user = User::factory()->create();

    expect($user->password)->not->toBe('password')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('creates an administrator through the factory state', function (): void {
    $user = User::factory()->administrator()->create();

    expect($user->role)->toBe(Role::Administrator);
});

it('seeds a single administrator user', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', Role::Administrator->value)->count())->toBe(1);
});
