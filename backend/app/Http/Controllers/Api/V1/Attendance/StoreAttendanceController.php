<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Enums\AttendanceStatusEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class StoreAttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function __invoke(StoreAttendanceRequest $request, GameMatch $gameMatch): JsonResponse
    {
        $data = $request->validated();
        $player = Player::findOrFail($data['player_id']);

        // A player confirms only their own attendance; administrators any.
        Gate::authorize('confirm', [Attendance::class, $player]);

        $attendance = $this->attendanceService->confirm(
            $player,
            $gameMatch,
            AttendanceStatusEnum::from($data['status']),
        );

        return ApiResponse::success(
            new AttendanceResource($attendance->load('player')),
            status: $attendance->wasRecentlyCreated ? 201 : 200,
        );
    }
}
