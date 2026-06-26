<?php

namespace App\Http\Resources;

use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameMatch
 */
class GameMatchResource extends JsonResource
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
            'date' => $this->date?->toDateString(),
            'status' => $this->status,
            'teams' => GameMatchTeamResource::collection($this->whenLoaded('teams')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
