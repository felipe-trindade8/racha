<?php

namespace App\Http\Requests\Matches;

use App\Enums\GameMatchStatusEnum;
use App\Models\GameMatch;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordGameMatchScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Scoring is a match write, so it reuses the `update` ability: administrators
     * are allowed and players are denied (403).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('gameMatch')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Player ratings are optional; each must reference a player rostered on one
     * of this match's two teams and carry a 1-5 rating.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $match = $this->route('gameMatch');
        $teamIds = $match instanceof GameMatch ? [$match->team_a_id, $match->team_b_id] : [];

        return [
            'team_a_result' => ['required', 'string', 'max:255'],
            'team_b_result' => ['required', 'string', 'max:255'],
            'player_ratings' => ['sometimes', 'array'],
            'player_ratings.*.team_player_id' => [
                'required',
                'integer',
                Rule::exists('team_players', 'id')->whereIn('game_match_team_id', $teamIds),
            ],
            'player_ratings.*.game_rating' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * A finished match is locked: it cannot be scored again until it is reopened
     * (status back to `planned`). This keeps a settled result from being
     * overwritten underneath its finished state.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $match = $this->route('gameMatch');

            if ($match instanceof GameMatch && $match->status === GameMatchStatusEnum::Finished) {
                $validator->errors()->add(
                    'status',
                    'A finished match cannot be scored again. Reopen it before scoring.',
                );
            }
        });
    }
}
