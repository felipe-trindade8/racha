<?php

namespace App\Http\Controllers\Api\V1\Players;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;

class StorePlayerController extends Controller
{
    public function __construct(private readonly PlayerService $playerService) {}

    public function __invoke(StorePlayerRequest $request): JsonResponse
    {
        $player = $this->playerService->create($request->validated());

        return ApiResponse::success(new PlayerResource($player->load('positions')), status: 201);
    }
}
