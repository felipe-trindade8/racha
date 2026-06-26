<?php

use App\Models\GameMatchTeam;
use App\Models\Player;
use App\Models\Position;
use App\Models\TeamPlayer;
use Database\Seeders\PositionSeeder;

it('relates a team player back to its player', function (): void {
    $player = Player::factory()->create();
    $teamPlayer = TeamPlayer::factory()->create(['player_id' => $player->id]);

    expect($teamPlayer->player)->toBeInstanceOf(Player::class)
        ->and($teamPlayer->player->id)->toBe($player->id);
});

it('relates a team player back to its team', function (): void {
    $team = GameMatchTeam::factory()->create();
    $teamPlayer = TeamPlayer::factory()->forTeam($team)->create();

    expect($teamPlayer->gameMatchTeam)->toBeInstanceOf(GameMatchTeam::class)
        ->and($teamPlayer->gameMatchTeam->id)->toBe($team->id);
});

it('relates a team to its rostered players', function (): void {
    $team = GameMatchTeam::factory()->create();
    TeamPlayer::factory()->forTeam($team)->count(3)->create();

    expect($team->teamPlayers)->toHaveCount(3)
        ->and($team->teamPlayers->first())->toBeInstanceOf(TeamPlayer::class);
});

it('relates a team player to its position', function (): void {
    $this->seed(PositionSeeder::class);
    $midfielder = Position::where('code', 'MID')->firstOrFail();
    $teamPlayer = TeamPlayer::factory()->forPosition($midfielder)->create();

    expect($teamPlayer->position)->toBeInstanceOf(Position::class)
        ->and($teamPlayer->position->code)->toBe('MID');
});

it('leaves the position unassigned by default', function (): void {
    $teamPlayer = TeamPlayer::factory()->create();

    expect($teamPlayer->position_id)->toBeNull()
        ->and($teamPlayer->position)->toBeNull();
});

it('casts is_starter to a boolean', function (): void {
    $starter = TeamPlayer::factory()->starter()->create();
    $reserve = TeamPlayer::factory()->create();

    expect($starter->is_starter)->toBeTrue()
        ->and($reserve->is_starter)->toBeFalse();
});

it('defaults is_starter to false at the database level', function (): void {
    $teamPlayer = TeamPlayer::factory()->create();

    expect($teamPlayer->fresh()->is_starter)->toBeFalse();
});

it('deletes the rostered players when the team is deleted', function (): void {
    $team = GameMatchTeam::factory()->create();
    TeamPlayer::factory()->forTeam($team)->count(2)->create();

    $team->delete();

    expect(TeamPlayer::count())->toBe(0);
});
