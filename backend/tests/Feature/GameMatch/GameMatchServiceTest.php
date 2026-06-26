<?php

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use App\Models\Player;
use App\Models\Position;
use App\Models\TeamPlayer;
use App\Services\GameMatchService;
use Database\Seeders\PositionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->service = app(GameMatchService::class);
});

/**
 * Build a valid two-team payload from the given players.
 *
 * @param  array<int, int>  $teamAPlayerIds
 * @param  array<int, int>  $teamBPlayerIds
 * @return array<string, mixed>
 */
function matchPayload(array $teamAPlayerIds, array $teamBPlayerIds): array
{
    return [
        'date' => '2026-07-04',
        'teams' => [
            ['team_name' => 'Team A', 'players' => array_map(fn (int $id): array => ['player_id' => $id], $teamAPlayerIds)],
            ['team_name' => 'Team B', 'players' => array_map(fn (int $id): array => ['player_id' => $id], $teamBPlayerIds)],
        ],
    ];
}

it('creates a match with its two teams and rosters atomically', function (): void {
    $players = Player::factory()->count(4)->create();

    $match = $this->service->create(matchPayload(
        [$players[0]->id, $players[1]->id],
        [$players[2]->id, $players[3]->id],
    ));

    expect($match)->toBeInstanceOf(GameMatch::class)
        ->and($match->status)->toBe(GameMatchStatusEnum::Planned)
        ->and($match->team_a_id)->not->toBeNull()
        ->and($match->team_b_id)->not->toBeNull()
        ->and($match->teams)->toHaveCount(2);

    expect(GameMatch::count())->toBe(1)
        ->and(GameMatchTeam::count())->toBe(2)
        ->and(TeamPlayer::count())->toBe(4);

    expect($match->teamA->teamPlayers)->toHaveCount(2)
        ->and($match->teamB->teamPlayers)->toHaveCount(2);
});

it('persists the roster details for each player', function (): void {
    $this->seed(PositionSeeder::class);
    $goalkeeper = Position::where('code', 'GK')->firstOrFail();
    $players = Player::factory()->count(2)->create();

    $match = $this->service->create([
        'date' => '2026-07-04',
        'teams' => [
            ['team_name' => 'Team A', 'players' => [
                ['player_id' => $players[0]->id, 'position_id' => $goalkeeper->id, 'is_starter' => true],
            ]],
            ['team_name' => 'Team B', 'players' => [
                ['player_id' => $players[1]->id],
            ]],
        ],
    ]);

    $starter = $match->teamA->teamPlayers->first();
    $reserve = $match->teamB->teamPlayers->first();

    expect($starter->player_id)->toBe($players[0]->id)
        ->and($starter->position_id)->toBe($goalkeeper->id)
        ->and($starter->is_starter)->toBeTrue()
        ->and($reserve->position_id)->toBeNull()
        ->and($reserve->is_starter)->toBeFalse();
});

it('rejects a match with fewer than two teams', function (): void {
    $player = Player::factory()->create();

    $this->service->create([
        'date' => '2026-07-04',
        'teams' => [
            ['team_name' => 'Team A', 'players' => [['player_id' => $player->id]]],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects a match with more than two teams', function (): void {
    $players = Player::factory()->count(3)->create();

    $payload = matchPayload([$players[0]->id], [$players[1]->id]);
    $payload['teams'][] = ['team_name' => 'Team C', 'players' => [['player_id' => $players[2]->id]]];

    $this->service->create($payload);
})->throws(ValidationException::class);

it('rejects a player rostered on both teams', function (): void {
    $shared = Player::factory()->create();
    $other = Player::factory()->create();

    $this->service->create(matchPayload([$shared->id], [$shared->id, $other->id]));
})->throws(ValidationException::class);

it('persists nothing when validation fails', function (): void {
    $shared = Player::factory()->create();

    try {
        $this->service->create(matchPayload([$shared->id], [$shared->id]));
    } catch (ValidationException) {
        // expected — assert no partial match was written below
    }

    expect(GameMatch::count())->toBe(0)
        ->and(GameMatchTeam::count())->toBe(0)
        ->and(TeamPlayer::count())->toBe(0);
});
