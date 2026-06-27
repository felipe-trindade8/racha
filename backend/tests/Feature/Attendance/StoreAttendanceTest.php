<?php

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

/** Authenticate as the given user and return the test instance with its token. */
function asUser(User $user): object
{
    return test()->withToken($user->createToken('api')->plainTextToken);
}

it('lets a player confirm their own attendance', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(201)
        ->assertJsonPath('data.playerId', $player->id)
        ->assertJsonPath('data.gameMatchId', $match->id)
        ->assertJsonPath('data.status', AttendanceStatusEnum::Available->value)
        ->assertJsonPath('data.confirmed', true)
        ->assertJsonPath('data.player.id', $player->id);

    expect(Attendance::count())->toBe(1);
});

it('overwrites the prior status when confirming again', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(201);

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'injured'])
        ->assertStatus(200)
        ->assertJsonPath('data.status', AttendanceStatusEnum::Injured->value);

    expect(Attendance::count())->toBe(1);
});

it('forbids a player from confirming another player attendance', function (): void {
    $own = Player::factory()->create();
    $other = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $own->id]);
    $match = GameMatch::factory()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", [
            'player_id' => $other->id,
            'status' => 'available',
        ])
        ->assertStatus(403);

    expect(Attendance::count())->toBe(0);
});

it('lets an administrator confirm attendance for a specified player', function (): void {
    $admin = User::factory()->administrator()->create();
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    asUser($admin)
        ->postJson("/api/v1/matches/{$match->id}/attendance", [
            'player_id' => $player->id,
            'status' => 'missing',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.playerId', $player->id)
        ->assertJsonPath('data.status', AttendanceStatusEnum::Missing->value);
});

it('requires the administrator to specify a player when they have no linked one', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();

    asUser($admin)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['player_id']);
});

it('validates that the status is required and valid', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'unknown'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('rejects confirming attendance for a finished match', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->finished()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['game_match_id']);

    expect(Attendance::count())->toBe(0);
});

it('rejects confirming attendance for an inactive player', function (): void {
    $player = Player::factory()->inactive()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->create();

    asUser($user)
        ->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['player_id']);

    expect(Attendance::count())->toBe(0);
});

it('requires authentication to confirm attendance', function (): void {
    $match = GameMatch::factory()->create();

    $this->postJson("/api/v1/matches/{$match->id}/attendance", ['status' => 'available'])
        ->assertStatus(401);
});
