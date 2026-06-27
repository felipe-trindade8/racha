<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The per-player rule (a player confirms only their own attendance) depends
     * on the validated target player, so it is applied in the controller once
     * the player is resolved. Authentication is enforced by the route's
     * `auth:sanctum` middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Default the target player to the authenticated user's own linked player
     * when none is specified, so a player can confirm their own attendance
     * without passing a redundant `player_id`.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('player_id') && $this->user()?->player_id !== null) {
            $this->merge(['player_id' => $this->user()->player_id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'status' => ['required', Rule::enum(AttendanceStatusEnum::class)],
        ];
    }
}
