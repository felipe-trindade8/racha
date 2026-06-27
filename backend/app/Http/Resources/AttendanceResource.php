<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
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
            'gameMatchId' => $this->game_match_id,
            'status' => $this->status,
            'confirmed' => $this->confirmed,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
