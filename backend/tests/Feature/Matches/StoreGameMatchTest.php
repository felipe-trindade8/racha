<?php

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Models\User;

/**
 * Build a valid two-team create payload from the given players.
 *
 * @param  array<int, int>  $teamAPlayerIds
 * @param  array<int, int>  $teamBPlayerIds
 * @return array<string, mixed>
 */
function storeMatchPayload(array $teamAPlayerIds, array $teamBPlayerIds): array
{
    return [
        'date' => '2026-07-04',
        'teams' => [
            ['team_name' => 'Team A', 'players' => array_map(fn (int $id): array => ['player_id' => $id], $teamAPlayerIds)],
            ['team_name' => 'Team B', 'players' => array_map(fn (int $id): array => ['player_id' => $id], $teamBPlayerIds)],
        ],
    ];
}

it('lets an administrator create a match with two teams and rosters', function (): void {
    $admin = User::factory()->administrator()->create();
    $players = Player::factory()->count(2)->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', storeMatchPayload([$players[0]->id], [$players[1]->id]))
        ->assertStatus(201)
        ->assertJsonPath('data.status', GameMatchStatusEnum::Planned->value)
        ->assertJsonFragment(['playerId' => $players[0]->id]);

    expect(GameMatch::count())->toBe(1)
        ->and(GameMatchTeam::count())->toBe(2)
        ->and(TeamPlayer::count())->toBe(2);
});

it('forbids a player from creating a match', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $rostered = Player::factory()->count(2)->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', storeMatchPayload([$rostered[0]->id], [$rostered[1]->id]))
        ->assertStatus(403);

    expect(GameMatch::count())->toBe(0);
});

it('validates the required fields', function (): void {
    $admin = User::factory()->administrator()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date', 'teams']);
});

it('rejects a non-existent player', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', storeMatchPayload([$player->id], [999]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['teams.1.players.0.player_id']);
});

it('rejects a match without exactly two teams', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', [
            'date' => '2026-07-04',
            'teams' => [
                ['team_name' => 'Team A', 'players' => [['player_id' => $player->id]]],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['teams']);
});

it('rejects a player rostered on both teams', function (): void {
    $admin = User::factory()->administrator()->create();
    $shared = Player::factory()->create();
    $other = Player::factory()->create();

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->postJson('/api/v1/matches', storeMatchPayload([$shared->id], [$shared->id, $other->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['players']);
});

it('requires authentication to create a match', function (): void {
    $this->postJson('/api/v1/matches', [])->assertStatus(401);
});
