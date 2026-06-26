<?php

namespace App\Http\Resources;

use App\Models\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Flat history view of a finished match: each side's result alongside the match.
 *
 * The team fields are populated from the joined columns selected in
 * GameMatchService::history (team_a_name/result, team_b_name/result).
 *
 * @mixin GameMatch
 */
class GameMatchHistoryResource extends JsonResource
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
            'teamA' => [
                'id' => $this->team_a_id,
                'teamName' => $this->team_a_name,
                'result' => $this->team_a_result,
            ],
            'teamB' => [
                'id' => $this->team_b_id,
                'teamName' => $this->team_b_name,
                'result' => $this->team_b_result,
            ],
        ];
    }
}
