<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinancialTransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('financialTransaction')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Every field is optional so the request supports partial updates. The
     * status is intentionally absent: it is transitioned through the dedicated
     * pay endpoint, not the generic update.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'player_id' => ['sometimes', 'nullable', 'integer', 'exists:players,id'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'type' => ['sometimes', 'required', Rule::enum(FinancialTransactionTypeEnum::class)],
            'date' => ['sometimes', 'required', 'date_format:Y-m-d'],
        ];
    }
}
