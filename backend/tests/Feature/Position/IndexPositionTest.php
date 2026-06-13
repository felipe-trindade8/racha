<?php

use App\Models\User;
use Database\Seeders\PositionSeeder;

it('returns the reference list of positions for an authenticated user', function (): void {
    $this->seed(PositionSeeder::class);
    $user = User::factory()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/positions')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name']]])
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.code', 'GK');
});

it('rejects an unauthenticated request', function (): void {
    $this->getJson('/api/v1/positions')
        ->assertUnauthorized();
});
