<?php

namespace App\Http\Requests\Matches;

use App\Models\GameMatch;
use Illuminate\Foundation\Http\FormRequest;

class StoreGameMatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', GameMatch::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * These rules validate the shape of the payload only. The structural rules —
     * exactly two teams and no player rostered more than once — are owned by
     * GameMatchService and surface as a 422 from there.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'teams' => ['required', 'array'],
            'teams.*.team_name' => ['required', 'string', 'max:255'],
            'teams.*.players' => ['sometimes', 'array'],
            'teams.*.players.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'teams.*.players.*.position_id' => ['sometimes', 'nullable', 'integer', 'exists:positions,id'],
            'teams.*.players.*.is_starter' => ['sometimes', 'boolean'],
        ];
    }
}
