<?php

use App\Models\Player;
use App\Models\User;

it('returns a paginated list of players for an administrator', function (): void {
    Player::factory()->count(3)->create();
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/players')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'nickname', 'rating', 'status', 'phone', 'positions', 'createdAt', 'updatedAt']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.total', 3);
});

it('forbids a player from listing players', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/players')
        ->assertStatus(403);
});

it('requires authentication to list players', function (): void {
    $this->getJson('/api/v1/players')
        ->assertStatus(401);
});
