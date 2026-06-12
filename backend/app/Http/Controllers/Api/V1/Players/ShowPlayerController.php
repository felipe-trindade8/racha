<?php

namespace App\Http\Controllers\Api\V1\Players;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ShowPlayerController extends Controller
{
    public function __invoke(Player $player): JsonResponse
    {
        Gate::authorize('view', $player);

        return ApiResponse::success(new PlayerResource($player->load('positions')));
    }
}
