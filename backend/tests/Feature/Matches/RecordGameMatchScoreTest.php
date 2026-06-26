<?php

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Models\User;

function asAdmin(): string
{
    return User::factory()->administrator()->create()->createToken('api')->plainTextToken;
}

it('lets an administrator record the score and finish the match', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '3',
            'team_b_result' => '1',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', GameMatchStatusEnum::Finished->value);

    $match->refresh();

    expect($match->status)->toBe(GameMatchStatusEnum::Finished)
        ->and($match->teamA->result)->toBe('3')
        ->and($match->teamB->result)->toBe('1');
});

it('records optional per-player game ratings', function (): void {
    $match = GameMatch::factory()->withTeams()->create();
    $teamPlayer = TeamPlayer::factory()->forTeam($match->teamA)->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '2',
            'team_b_result' => '2',
            'player_ratings' => [
                ['team_player_id' => $teamPlayer->id, 'game_rating' => 4],
            ],
        ])
        ->assertOk();

    expect($teamPlayer->fresh()->game_rating)->toBe(4);
});

it('forbids scoring a match that is already finished', function (): void {
    $match = GameMatch::factory()->withTeams()->finished()->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '3',
            'team_b_result' => '1',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('requires both team results', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['team_a_result', 'team_b_result']);
});

it('rejects a game rating outside the 1-5 range', function (): void {
    $match = GameMatch::factory()->withTeams()->create();
    $teamPlayer = TeamPlayer::factory()->forTeam($match->teamA)->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '1',
            'team_b_result' => '0',
            'player_ratings' => [
                ['team_player_id' => $teamPlayer->id, 'game_rating' => 6],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['player_ratings.0.game_rating']);
});

it('rejects a rating for a player not rostered in the match', function (): void {
    $match = GameMatch::factory()->withTeams()->create();
    $otherMatch = GameMatch::factory()->withTeams()->create();
    $foreignPlayer = TeamPlayer::factory()->forTeam($otherMatch->teamA)->create();

    $this->withToken(asAdmin())
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '1',
            'team_b_result' => '0',
            'player_ratings' => [
                ['team_player_id' => $foreignPlayer->id, 'game_rating' => 3],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['player_ratings.0.team_player_id']);
});

it('forbids a player from recording a score', function (): void {
    $player = Player::factory()->create();
    $user = User::factory()->create(['player_id' => $player->id]);
    $match = GameMatch::factory()->withTeams()->create();

    $this->withToken($user->createToken('api')->plainTextToken)
        ->patchJson("/api/v1/matches/{$match->id}/score", [
            'team_a_result' => '3',
            'team_b_result' => '1',
        ])
        ->assertStatus(403);

    expect($match->fresh()->status)->toBe(GameMatchStatusEnum::Planned);
});

it('requires authentication to record a score', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $this->patchJson("/api/v1/matches/{$match->id}/score", [
        'team_a_result' => '3',
        'team_b_result' => '1',
    ])->assertStatus(401);
});
