<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attempt(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($result === null) {
            return ApiResponse::error('Invalid credentials.', status: 401);
        }

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => $result['user'],
        ]);
    }
}
