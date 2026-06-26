<?php

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

it('lets an administrator update the match date', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->withTeams()->create(['date' => '2026-07-04']);

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/matches/{$match->id}", ['date' => '2026-07-11'])
        ->assertOk()
        ->assertJsonPath('data.date', '2026-07-11');

    expect($match->fresh()->date->toDateString())->toBe('2026-07-11');
});

it('forbids a player from updating a match', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->withTeams()->create(['date' => '2026-07-04']);

    $this->withToken($user->createToken('api')->plainTextToken)
        ->putJson("/api/v1/matches/{$match->id}", ['date' => '2026-07-11'])
        ->assertStatus(403);

    expect($match->fresh()->date->toDateString())->toBe('2026-07-04');
});

it('validates the date', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->withTeams()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->putJson("/api/v1/matches/{$match->id}", ['date' => 'not-a-date'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

it('requires authentication to update a match', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $this->putJson("/api/v1/matches/{$match->id}", ['date' => '2026-07-11'])->assertStatus(401);
});
