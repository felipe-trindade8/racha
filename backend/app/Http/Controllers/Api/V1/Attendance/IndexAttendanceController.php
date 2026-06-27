<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\IndexAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\GameMatch;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class IndexAttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function __invoke(IndexAttendanceRequest $request, GameMatch $gameMatch): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $attendances = $this->attendanceService->paginate($gameMatch, $perPage);

        $attendances->through(fn (Attendance $attendance): AttendanceResource => new AttendanceResource($attendance));

        return ApiResponse::paginated($attendances);
    }
}
