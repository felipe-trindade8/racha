<?php

namespace App\Http\Controllers\Api\V1\Positions;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

/**
 * Read-only reference list of the positions a player can hold.
 *
 * Any authenticated user may read this list (it backs the player form's
 * positions multi-select), so no policy gating is needed beyond the route's
 * `auth:sanctum` middleware.
 */
class IndexPositionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $positions = Position::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        return ApiResponse::success(PositionResource::collection($positions));
    }
}
