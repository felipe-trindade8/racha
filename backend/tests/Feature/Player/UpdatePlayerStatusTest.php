<?php

use App\Enums\PlayerStatusEnum;
use App\Models\Player;
use App\Models\User;

it('lets an administrator change a player status', function (string $status): void {
    $player = Player::factory()->create(['status' => PlayerStatusEnum::Active]);
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/players/{$player->id}/status", ['status' => $status])
        ->assertOk()
        ->assertJsonPath('data.status', $status);

    expect($player->fresh()->status)->toBe(PlayerStatusEnum::from($status));
})->with(['inactive', 'active', 'injured']);

it('validates the status when updating a player status', function (): void {
    $player = Player::factory()->create();
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/players/{$player->id}/status", ['status' => 'unknown'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('forbids a player from changing their own status', function (): void {
    $player = Player::factory()->create(['status' => PlayerStatusEnum::Active]);
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/players/{$player->id}/status", ['status' => 'inactive'])
        ->assertStatus(403);

    expect($player->fresh()->status)->toBe(PlayerStatusEnum::Active);
});

it('requires authentication to change a player status', function (): void {
    $player = Player::factory()->create();

    $this->patchJson("/api/v1/players/{$player->id}/status", ['status' => 'inactive'])
        ->assertStatus(401);
});
