<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Handles token-based authentication for the API.
 *
 * Credential verification and token issuing/revocation live here so the
 * controllers stay thin and free of business logic.
 */
class AuthService
{
    /**
     * Verify credentials and issue a personal access token.
     *
     * Returns the plain-text token and the authenticated user, or null when
     * the credentials do not match.
     *
     * @return array{token: string, user: User}|null
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user,
        ];
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
