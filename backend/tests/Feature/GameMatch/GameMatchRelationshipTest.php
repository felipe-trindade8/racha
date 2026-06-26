<?php

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use App\Models\GameMatchTeam;
use Illuminate\Support\Facades\DB;

it('relates a match to its two teams', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    expect($match->teams)->toHaveCount(2)
        ->and($match->teamA)->toBeInstanceOf(GameMatchTeam::class)
        ->and($match->teamB)->toBeInstanceOf(GameMatchTeam::class)
        ->and($match->teamA->id)->not->toBe($match->teamB->id);
});

it('relates a team back to its match', function (): void {
    $match = GameMatch::factory()->create();
    $team = GameMatchTeam::factory()->forMatch($match)->create();

    expect($team->gameMatch)->toBeInstanceOf(GameMatch::class)
        ->and($team->gameMatch->id)->toBe($match->id);
});

it('casts status to the GameMatchStatusEnum', function (): void {
    $planned = GameMatch::factory()->create();
    $finished = GameMatch::factory()->finished()->create();

    expect($planned->status)->toBe(GameMatchStatusEnum::Planned)
        ->and($finished->status)->toBe(GameMatchStatusEnum::Finished);
});

it('defaults the status to planned at the database level', function (): void {
    $id = DB::table('game_matches')->insertGetId(['date' => now()]);

    expect(GameMatch::findOrFail($id)->status)->toBe(GameMatchStatusEnum::Planned);
});

it('deletes the teams when the match is deleted', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $match->delete();

    expect(GameMatchTeam::count())->toBe(0);
});

it('nulls the team references when a team is deleted', function (): void {
    $match = GameMatch::factory()->withTeams()->create();

    $match->teamA->delete();

    expect($match->fresh()->team_a_id)->toBeNull();
});
