<?php

namespace App\Http\Resources;

use App\Models\TeamPlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeamPlayer
 */
class TeamPlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Keys are camelCase even though the underlying columns are snake_case, per
     * the API conventions in architecture.md.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'playerId' => $this->player_id,
            'positionId' => $this->position_id,
            'gameRating' => $this->game_rating,
            'isStarter' => $this->is_starter,
            'player' => new PlayerResource($this->whenLoaded('player')),
        ];
    }
}
