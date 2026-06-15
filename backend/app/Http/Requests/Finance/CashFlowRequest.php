<?php

namespace App\Http\Requests\Finance;

use App\Models\FinancialTransaction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashFlowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FinancialTransaction::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the query parameters.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m'],
            'status' => ['sometimes', Rule::in(['open', 'paid', 'all'])],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * `Y-m` strings sort lexicographically, so a plain comparison is enough to
     * enforce that the range is not inverted.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('from');
            $to = $this->input('to');

            if (is_string($from) && is_string($to) && $from !== '' && $to !== '' && $to < $from) {
                $validator->errors()->add('to', 'The to month must be on or after the from month.');
            }
        });
    }
}
