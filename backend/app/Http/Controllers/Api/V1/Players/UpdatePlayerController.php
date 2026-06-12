<?php

namespace App\Http\Controllers\Api\V1\Players;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;

class UpdatePlayerController extends Controller
{
    public function __construct(private readonly PlayerService $playerService) {}

    public function __invoke(UpdatePlayerRequest $request, Player $player): JsonResponse
    {
        $player = $this->playerService->update($player, $request->validated());

        return ApiResponse::success(new PlayerResource($player->load('positions')));
    }
}
