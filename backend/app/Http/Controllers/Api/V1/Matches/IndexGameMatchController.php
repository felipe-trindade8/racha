<?php

namespace App\Http\Controllers\Api\V1\Matches;

use App\Enums\GameMatchStatusEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Matches\IndexGameMatchRequest;
use App\Http\Resources\GameMatchResource;
use App\Models\GameMatch;
use App\Services\GameMatchService;
use Illuminate\Http\JsonResponse;

class IndexGameMatchController extends Controller
{
    public function __construct(private readonly GameMatchService $gameMatchService) {}

    public function __invoke(IndexGameMatchRequest $request): JsonResponse
    {
        $status = GameMatchStatusEnum::tryFrom((string) $request->validated('status'));

        $matches = $this->gameMatchService->paginate(
            $request->integer('per_page', 15),
            $status,
            $request->validated('date'),
        );

        $matches->through(
            fn (GameMatch $match): GameMatchResource => new GameMatchResource($match),
        );

        return ApiResponse::paginated($matches);
    }
}
