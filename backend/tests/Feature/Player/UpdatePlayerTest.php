<?php

use App\Models\Player;
use App\Models\User;

it('lets an administrator update any player', function (): void {
    $player = Player::factory()->create(['name' => 'Old Name']);
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/players/{$player->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect($player->fresh()->name)->toBe('New Name');
});

it('lets a player update their own linked record', function (): void {
    $player = Player::factory()->create(['nickname' => 'Old']);
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->putJson("/api/v1/players/{$player->id}", ['nickname' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.nickname', 'New');
});

it('forbids a player from updating another player record', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->putJson("/api/v1/players/{$other->id}", ['nickname' => 'Hacked'])
        ->assertStatus(403);
});

it('validates the payload when updating a player', function (): void {
    $player = Player::factory()->create();
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/players/{$player->id}", [
            'rating' => 9,
            'status' => 'unknown',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['rating', 'status']);
});

it('requires authentication to update a player', function (): void {
    $player = Player::factory()->create();

    $this->putJson("/api/v1/players/{$player->id}", ['name' => 'X'])
        ->assertStatus(401);
});
