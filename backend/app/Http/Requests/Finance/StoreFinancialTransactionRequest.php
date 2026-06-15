<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', FinancialTransaction::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The status is intentionally absent: new transactions always start `open`
     * and are transitioned through the dedicated pay endpoint.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'player_id' => ['nullable', 'integer', 'exists:players,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', Rule::enum(FinancialTransactionTypeEnum::class)],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
