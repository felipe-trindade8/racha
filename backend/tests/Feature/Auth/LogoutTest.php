<?php

use App\Models\User;

it('revokes the current access token on logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.message', 'Logged out.');

    expect($user->tokens()->count())->toBe(0);
});

it('rejects requests made with the revoked token afterwards', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    // The app is not rebooted between requests in a single test, so the auth
    // guard caches the resolved user; forget it to simulate a fresh request.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});

it('returns 401 when logging out without a token', function (): void {
    $this->postJson('/api/v1/auth/logout')
        ->assertStatus(401)
        ->assertJsonStructure(['message']);
});

it('only revokes the token used for the request', function (): void {
    $user = User::factory()->create();
    $user->createToken('other-device');
    $current = $user->createToken('api')->plainTextToken;

    $this->withToken($current)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(1);
});
