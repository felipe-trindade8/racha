<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Matches\GameMatchHistoryRequest;
use App\Http\Resources\GameMatchHistoryResource;
use App\Models\GameMatch;
use App\Services\GameMatchService;
use Illuminate\Http\JsonResponse;

class GameMatchHistoryController extends Controller
{
    public function __construct(private readonly GameMatchService $gameMatchService) {}

    public function __invoke(GameMatchHistoryRequest $request): JsonResponse
    {
        $matches = $this->gameMatchService->history($request->integer('per_page', 15));

        $matches->through(
            fn (GameMatch $match): GameMatchHistoryResource => new GameMatchHistoryResource($match),
        );

        return ApiResponse::paginated($matches);
    }
}
