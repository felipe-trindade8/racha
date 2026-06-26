<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Matches\StoreGameMatchRequest;
use App\Http\Resources\GameMatchResource;
use App\Services\GameMatchService;
use Illuminate\Http\JsonResponse;

class StoreGameMatchController extends Controller
{
    public function __construct(private readonly GameMatchService $gameMatchService) {}

    public function __invoke(StoreGameMatchRequest $request): JsonResponse
    {
        $match = $this->gameMatchService->create($request->validated());

        return ApiResponse::success(
            new GameMatchResource($match->load('teams.teamPlayers.player')),
            status: 201,
        );
    }
}
