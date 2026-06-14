<?php

namespace App\Http\Requests\Finance;

use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;

class GenerateMonthlyPaymentsRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'string', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
