<?php

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

it('lists matches paginated for an administrator', function (): void {
    $admin = User::factory()->administrator()->create();
    GameMatch::factory()->withTeams()->count(3)->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'date', 'status', 'teams' => [['id', 'teamName', 'result']]]],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.total', 3);
});

it('lets a player list matches', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    GameMatch::factory()->withTeams()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('filters matches by status', function (): void {
    $admin = User::factory()->administrator()->create();
    GameMatch::factory()->create();
    GameMatch::factory()->finished()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches?status=finished')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.status', 'finished');
});

it('filters matches by date', function (): void {
    $admin = User::factory()->administrator()->create();
    GameMatch::factory()->create(['date' => '2026-07-04']);
    GameMatch::factory()->create(['date' => '2026-07-11']);

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches?date=2026-07-04')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.date', '2026-07-04');
});

it('rejects an invalid status filter', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches?status=foo')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('requires authentication to list matches', function (): void {
    $this->getJson('/api/v1/matches')->assertStatus(401);
});
