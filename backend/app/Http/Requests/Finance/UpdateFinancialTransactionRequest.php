<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Models\FinancialTransaction;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * Configure the validator instance.
     *
     * A paid transaction is locked: its details can only be edited after it is
     * reopened through the status endpoint. This keeps a settled record from
     * being changed underneath its paid state.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $transaction = $this->route('financialTransaction');

            if ($transaction instanceof FinancialTransaction && $transaction->status === FinancialTransactionStatusEnum::Paid) {
                $validator->errors()->add(
                    'status',
                    'A paid transaction cannot be edited. Reopen it before editing.',
                );
            }
        });
    }
}
