<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexFinancialTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FinancialTransaction::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the query filters.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(FinancialTransactionTypeEnum::class)],
            'status' => ['sometimes', Rule::enum(FinancialTransactionStatusEnum::class)],
            'month' => ['sometimes', 'date_format:Y-m'],
            'player_id' => ['sometimes', 'integer', 'exists:players,id'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
