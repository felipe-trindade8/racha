<?php

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;

/**
 * Create a finished match dated on the given day with both teams' results set.
 */
function historyFinishedMatch(string $date, string $teamAResult, string $teamBResult): GameMatch
{
    $match = GameMatch::factory()->withTeams()->finished()->create(['date' => $date]);
    $match->teamA->update(['result' => $teamAResult]);
    $match->teamB->update(['result' => $teamBResult]);

    return $match;
}

it('returns only finished matches', function (): void {
    $admin = User::factory()->administrator()->create();
    $finished = historyFinishedMatch('2026-06-20', '3', '1');
    GameMatch::factory()->withTeams()->create(['date' => '2026-06-27']); // planned

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/history')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $finished->id)
        ->assertJsonPath('data.0.status', 'finished');
});

it('orders matches newest first', function (): void {
    $admin = User::factory()->administrator()->create();
    $older = historyFinishedMatch('2026-06-13', '2', '2');
    $newer = historyFinishedMatch('2026-06-20', '1', '0');

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/history')
        ->assertOk()
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id);
});

it('includes both teams results', function (): void {
    $admin = User::factory()->administrator()->create();
    historyFinishedMatch('2026-06-20', '3', '1');

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/history')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'date', 'status', 'teamA' => ['id', 'teamName', 'result'], 'teamB' => ['id', 'teamName', 'result']]],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('data.0.teamA.result', '3')
        ->assertJsonPath('data.0.teamB.result', '1');
});

it('paginates the history', function (): void {
    $admin = User::factory()->administrator()->create();
    historyFinishedMatch('2026-06-13', '1', '0');
    historyFinishedMatch('2026-06-20', '2', '1');
    historyFinishedMatch('2026-06-27', '3', '2');

    $this->withToken($admin->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/history?per_page=2')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonCount(2, 'data');
});

it('lets a player view the match history', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    historyFinishedMatch('2026-06-20', '3', '1');

    $this->withToken($user->createToken('api')->plainTextToken)
        ->getJson('/api/v1/matches/history')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('requires authentication to view the match history', function (): void {
    $this->getJson('/api/v1/matches/history')->assertStatus(401);
});
