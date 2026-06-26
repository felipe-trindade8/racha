<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\GameMatchResource;
use App\Models\GameMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShowGameMatchController extends Controller
{
    public function __invoke(GameMatch $gameMatch): JsonResponse
    {
        Gate::authorize('view', $gameMatch);

        return ApiResponse::success(
            new GameMatchResource($gameMatch->load('teams.teamPlayers.player')),
        );
    }
}
