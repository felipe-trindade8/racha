<?php

use App\Models\User;

it('returns a token and user payload for valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'player@racha.test',
        'password' => 'secret-password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'player@racha.test',
        'password' => 'secret-password',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', 'player@racha.test')
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]]);
});

it('does not expose the password in the user payload', function (): void {
    User::factory()->create([
        'email' => 'player@racha.test',
        'password' => 'secret-password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'player@racha.test',
        'password' => 'secret-password',
    ])
        ->assertOk()
        ->assertJsonMissingPath('data.user.password');
});

it('returns 401 in the standard envelope for invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'player@racha.test',
        'password' => 'secret-password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'player@racha.test',
        'password' => 'wrong-password',
    ])
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Invalid credentials.']);
});

it('returns 401 for an unknown email', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@racha.test',
        'password' => 'secret-password',
    ])
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Invalid credentials.']);
});

it('returns 422 with field errors when credentials are missing', function (): void {
    $this->postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
});

it('returns 422 when the email is malformed', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => 'secret-password',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['email']]);
});
