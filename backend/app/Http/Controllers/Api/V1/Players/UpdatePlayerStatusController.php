<?php

namespace App\Http\Controllers\Api\V1\Players;

use App\Enums\PlayerStatusEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdatePlayerStatusRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;

class UpdatePlayerStatusController extends Controller
{
    public function __construct(private readonly PlayerService $playerService) {}

    public function __invoke(UpdatePlayerStatusRequest $request, Player $player): JsonResponse
    {
        $player = $this->playerService->updateStatus(
            $player,
            PlayerStatusEnum::from($request->validated('status')),
        );

        return ApiResponse::success(new PlayerResource($player->load('positions')));
    }
}
