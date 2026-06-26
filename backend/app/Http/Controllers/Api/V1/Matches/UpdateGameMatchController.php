<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Matches\UpdateGameMatchRequest;
use App\Http\Resources\GameMatchResource;
use App\Models\GameMatch;
use App\Services\GameMatchService;
use Illuminate\Http\JsonResponse;

class UpdateGameMatchController extends Controller
{
    public function __construct(private readonly GameMatchService $gameMatchService) {}

    public function __invoke(UpdateGameMatchRequest $request, GameMatch $gameMatch): JsonResponse
    {
        $match = $this->gameMatchService->update($gameMatch, $request->validated());

        return ApiResponse::success(
            new GameMatchResource($match->load('teams')),
        );
    }
}
