<?php

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Models\User;

it('shows a match with its teams and rosters', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->withTeams()->create();
    $player = Player::factory()->create();
    TeamPlayer::factory()->forTeam($match->teamA)->create(['player_id' => $player->id]);

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson("/api/v1/matches/{$match->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'date', 'status', 'teams' => [['id', 'teamName', 'result', 'players']]],
        ])
        ->assertJsonPath('data.id', $match->id)
        ->assertJsonFragment(['playerId' => $player->id]);
});

it('lets a player view a match', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->withTeams()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson("/api/v1/matches/{$match->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $match->id);
});

it('returns 404 for a missing match', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/999')
        ->assertStatus(404);
});

it('requires authentication to view a match', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $this->getJson("/api/v1/matches/{$match->id}")->assertStatus(401);
});
