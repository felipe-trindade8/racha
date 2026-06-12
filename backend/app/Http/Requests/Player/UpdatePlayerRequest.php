<?php

namespace App\Http\Requests\Player;

use App\Enums\PlayerStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('player')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Every field is optional so the request supports partial updates; the
     * constraints still apply to any field that is present.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'status' => ['sometimes', 'required', Rule::enum(PlayerStatusEnum::class)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position_ids' => ['sometimes', 'array'],
            'position_ids.*' => ['integer', 'distinct', 'exists:positions,id'],
        ];
    }
}
