<?php

use App\Models\Player;
use App\Models\User;

it('lets an administrator view any player', function (): void {
    $player = Player::factory()->create();
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson("/api/v1/players/{$player->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $player->id)
        ->assertJsonPath('data.name', $player->name);
});

it('lets a player view their own linked record', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson("/api/v1/players/{$player->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $player->id);
});

it('forbids a player from viewing another player record', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson("/api/v1/players/{$other->id}")
        ->assertStatus(403);
});

it('returns 404 for an unknown player', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/players/999')
        ->assertStatus(404);
});

it('requires authentication to view a player', function (): void {
    $player = Player::factory()->create();

    $this->getJson("/api/v1/players/{$player->id}")
        ->assertStatus(401);
});
