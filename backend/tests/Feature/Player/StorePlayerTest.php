<?php

use App\Enums\PlayerStatusEnum;
use App\Models\Player;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\PositionSeeder;

it('lets an administrator create a player with positions', function (): void {
    $this->seed(PositionSeeder::class);
    $admin = User::factory()->administrator()->create();
    $positionIds = Position::whereIn('code', ['GK', 'DEF'])->pluck('id')->all();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/players', [
            'name' => 'Pelé',
            'nickname' => 'O Rei',
            'rating' => 5,
            'status' => PlayerStatusEnum::Active->value,
            'phone' => '+55 11 99999-0000',
            'position_ids' => $positionIds,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Pelé')
        ->assertJsonPath('data.nickname', 'O Rei')
        ->assertJsonStructure(['data' => ['id', 'createdAt', 'updatedAt', 'positions' => [['id', 'code', 'name']]]]);

    expect(Player::where('name', 'Pelé')->first()->positions)->toHaveCount(2);
});

it('forbids a player from creating a player', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->postJson('/api/v1/players', [
            'name' => 'Garrincha',
            'rating' => 5,
            'status' => PlayerStatusEnum::Active->value,
        ])
        ->assertStatus(403);
});

it('validates the payload when creating a player', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/players', [
            'nickname' => 'No Name',
            'rating' => 9,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'rating', 'status']);
});

it('requires authentication to create a player', function (): void {
    $this->postJson('/api/v1/players', [])
        ->assertStatus(401);
});
