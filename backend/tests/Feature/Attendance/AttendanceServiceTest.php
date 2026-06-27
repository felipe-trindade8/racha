<?php

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\AttendanceService;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->service = app(AttendanceService::class);
});

it('confirms a planned-match attendance for an active player', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    $attendance = $this->service->confirm($player, $match, AttendanceStatusEnum::Available);

    expect($attendance)->toBeInstanceOf(Attendance::class)
        ->and($attendance->player_id)->toBe($player->id)
        ->and($attendance->game_match_id)->toBe($match->id)
        ->and($attendance->status)->toBe(AttendanceStatusEnum::Available)
        ->and($attendance->confirmed)->toBeTrue()
        ->and(Attendance::count())->toBe(1);
});

it('overwrites the prior status when confirming again (upsert)', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    $first = $this->service->confirm($player, $match, AttendanceStatusEnum::Available);
    $second = $this->service->confirm($player, $match, AttendanceStatusEnum::Injured);

    expect($second->id)->toBe($first->id)
        ->and($second->status)->toBe(AttendanceStatusEnum::Injured)
        ->and($second->confirmed)->toBeTrue()
        // The upsert overwrites the single record rather than adding a row.
        ->and(Attendance::count())->toBe(1);
});

it('lets an active player report a per-match availability status', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    $injured = $this->service->confirm($player, $match, AttendanceStatusEnum::Injured);
    expect($injured->status)->toBe(AttendanceStatusEnum::Injured);

    $missing = $this->service->confirm($player, GameMatch::factory()->create(), AttendanceStatusEnum::Missing);
    expect($missing->status)->toBe(AttendanceStatusEnum::Missing);
});

it('rejects confirming attendance for a finished match', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->finished()->create();

    $this->service->confirm($player, $match, AttendanceStatusEnum::Available);
})->throws(ValidationException::class);

it('rejects confirming attendance for an inactive player', function (): void {
    $player = Player::factory()->inactive()->create();
    $match = GameMatch::factory()->create();

    $this->service->confirm($player, $match, AttendanceStatusEnum::Available);
})->throws(ValidationException::class);

it('writes nothing when a confirmation is rejected', function (): void {
    $finishedMatch = GameMatch::factory()->finished()->create();
    $inactivePlayer = Player::factory()->inactive()->create();
    $activePlayer = Player::factory()->create();

    try {
        $this->service->confirm($activePlayer, $finishedMatch, AttendanceStatusEnum::Available);
    } catch (ValidationException) {
        // expected
    }

    try {
        $this->service->confirm($inactivePlayer, GameMatch::factory()->create(), AttendanceStatusEnum::Available);
    } catch (ValidationException) {
        // expected
    }

    expect(Attendance::count())->toBe(0);
});
