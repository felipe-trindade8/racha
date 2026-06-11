<?php

use App\Enums\RoleEnum;
use App\Models\User;

it('returns the authenticated user in the standard envelope', function (): void {
    $user = User::factory()->administrator()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', RoleEnum::Administrator->value);
});

it('does not expose the password of the authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonMissingPath('data.password');
});

it('returns 401 without a token', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertJsonStructure(['message']);
});
