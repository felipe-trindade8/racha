<?php

namespace App\Http\Resources;

use App\Models\GameMatchTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GameMatchTeam
 */
class GameMatchTeamResource extends JsonResource
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
            'teamName' => $this->team_name,
            'result' => $this->result,
        ];
    }
}
