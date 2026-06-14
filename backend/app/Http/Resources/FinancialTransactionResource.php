<?php

namespace App\Http\Resources;

use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FinancialTransaction
 */
class FinancialTransactionResource extends JsonResource
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
            'description' => $this->description,
            'amount' => $this->amount,
            'type' => $this->type,
            'date' => $this->date?->toDateString(),
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
