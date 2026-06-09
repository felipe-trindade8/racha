<?php

use App\Models\User;

it('runs migrations against the isolated test connection', function (): void {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
});

it('persists records via RefreshDatabase', function (): void {
    $user = User::factory()->create();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    expect(User::count())->toBe(1);
});
