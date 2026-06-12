<?php

namespace App\Http\Controllers\Api\V1\Players;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IndexPlayerController extends Controller
{
    public function __construct(private readonly PlayerService $playerService) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Player::class);

        $players = $this->playerService->paginate($request->integer('per_page', 15));

        $players->through(fn (Player $player): PlayerResource => new PlayerResource($player));

        return ApiResponse::paginated($players);
    }
}
