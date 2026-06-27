<?php

use App\Enums\AttendanceStatusEnum;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('relates an attendance back to its player', function (): void {
    $player = Player::factory()->create();
    $attendance = Attendance::factory()->forPlayer($player)->create();

    expect($attendance->player)->toBeInstanceOf(Player::class)
        ->and($attendance->player->id)->toBe($player->id);
});

it('relates an attendance back to its match', function (): void {
    $match = GameMatch::factory()->create();
    $attendance = Attendance::factory()->forMatch($match)->create();

    expect($attendance->gameMatch)->toBeInstanceOf(GameMatch::class)
        ->and($attendance->gameMatch->id)->toBe($match->id);
});

it('relates a player to its attendance records', function (): void {
    $player = Player::factory()->create();
    Attendance::factory()->forPlayer($player)->count(3)->create();

    expect($player->attendances)->toHaveCount(3)
        ->and($player->attendances->first())->toBeInstanceOf(Attendance::class);
});

it('relates a match to its attendance records', function (): void {
    $match = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->count(3)->create();

    expect($match->attendances)->toHaveCount(3)
        ->and($match->attendances->first())->toBeInstanceOf(Attendance::class);
});

it('casts status to the enum and confirmed to a boolean', function (): void {
    $injured = Attendance::factory()->injured()->confirmed()->create();
    $available = Attendance::factory()->create();
    $missing = Attendance::factory()->missing()->create();

    expect($injured->status)->toBe(AttendanceStatusEnum::Injured)
        ->and($injured->confirmed)->toBeTrue()
        ->and($available->status)->toBe(AttendanceStatusEnum::Available)
        ->and($available->confirmed)->toBeFalse()
        ->and($missing->status)->toBe(AttendanceStatusEnum::Missing);
});

it('defaults status to available and confirmed to false at the database level', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    // Insert without the defaulted columns to exercise the database defaults.
    $id = DB::table('attendances')->insertGetId([
        'player_id' => $player->id,
        'game_match_id' => $match->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $attendance = Attendance::findOrFail($id);

    expect($attendance->status)->toBe(AttendanceStatusEnum::Available)
        ->and($attendance->confirmed)->toBeFalse();
});

it('enforces a unique attendance per player and match', function (): void {
    $player = Player::factory()->create();
    $match = GameMatch::factory()->create();

    Attendance::factory()->forPlayer($player)->forMatch($match)->create();

    expect(fn () => Attendance::factory()->forPlayer($player)->forMatch($match)->create())
        ->toThrow(QueryException::class);
});

it('allows the same player to attend different matches', function (): void {
    $player = Player::factory()->create();

    Attendance::factory()->forPlayer($player)->count(2)->create();

    expect(Attendance::where('player_id', $player->id)->count())->toBe(2);
});

it('deletes the attendance records when the player is deleted', function (): void {
    $player = Player::factory()->create();
    Attendance::factory()->forPlayer($player)->count(2)->create();

    $player->delete();

    expect(Attendance::count())->toBe(0);
});

it('deletes the attendance records when the match is deleted', function (): void {
    $match = GameMatch::factory()->create();
    Attendance::factory()->forMatch($match)->count(2)->create();

    $match->delete();

    expect(Attendance::count())->toBe(0);
});
