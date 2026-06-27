<?php

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

/** Authenticate as the given user and return the test instance with its token. */
function asListUser(User $user): object
{
    return test()->withToken($user->createToken('api')->plainTextToken);
}

it('returns the match attendance list with player and status', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();
    $player = Player::factory()->create(['name' => 'Ana']);
    Attendance::factory()->forMatch($match)->forPlayer($player)->injured()->confirmed()->create();

    asListUser($admin)
        ->getJson("/api/v1/matches/{$match->id}/attendance")
        ->assertStatus(200)
        ->assertJsonPath('data.0.playerId', $player->id)
        ->assertJsonPath('data.0.status', AttendanceStatusEnum::Injured->value)
        ->assertJsonPath('data.0.confirmed', true)
        ->assertJsonPath('data.0.player.name', 'Ana')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.current_page', 1);
});

it('scopes the list to the given match', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();
    $otherMatch = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->count(2)->create();
    Attendance::factory()->forMatch($otherMatch)->count(3)->create();

    asListUser($admin)
        ->getJson("/api/v1/matches/{$match->id}/attendance")
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonCount(2, 'data');
});

it('orders the list by player name', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->forPlayer(Player::factory()->create(['name' => 'Zoe']))->create();
    Attendance::factory()->forMatch($match)->forPlayer(Player::factory()->create(['name' => 'Ana']))->create();

    asListUser($admin)
        ->getJson("/api/v1/matches/{$match->id}/attendance")
        ->assertStatus(200)
        ->assertJsonPath('data.0.player.name', 'Ana')
        ->assertJsonPath('data.1.player.name', 'Zoe');
});

it('paginates the list with the per_page filter', function (): void {
    $admin = User::factory()->administrator()->create();
    $match = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->count(5)->create();

    asListUser($admin)
        ->getJson("/api/v1/matches/{$match->id}/attendance?per_page=2")
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('lets a player view the attendance list', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->create();

    asListUser($user)
        ->getJson("/api/v1/matches/{$match->id}/attendance")
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 1);
});

it('requires authentication to view the attendance list', function (): void {
    $match = GameMatch::factory()->create();

    $this->getJson("/api/v1/matches/{$match->id}/attendance")
        ->assertStatus(401);
});
