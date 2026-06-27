<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Viewing the attendance list is allowed for every role (AttendancePolicy).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Attendance::class) ?? false;
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
