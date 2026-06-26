<?php

namespace App\Http\Requests\Matches;

use App\Models\GameMatch;
use Illuminate\Foundation\Http\FormRequest;

class GameMatchHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', GameMatch::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the query filters.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
