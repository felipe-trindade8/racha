<?php

namespace App\Http\Requests\Player;

use App\Enums\PlayerStatusEnum;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Player::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'status' => ['required', Rule::enum(PlayerStatusEnum::class)],
            'phone' => ['nullable', 'string', 'max:255'],
            'position_ids' => ['array'],
            'position_ids.*' => ['integer', 'distinct', 'exists:positions,id'],
        ];
    }
}
