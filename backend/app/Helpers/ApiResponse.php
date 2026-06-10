<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Builds the application's standard JSON envelopes.
 *
 * Success responses wrap the payload under a `data` key. Paginated responses
 * add a `meta` block with pagination details. Error responses expose a
 * human-readable `message` and an optional `errors` map.
 */
class ApiResponse
{
    /**
     * Standard success envelope: { "data": ... }.
     */
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Paginated list envelope: { "data": [...], "meta": { ... } }.
     */
    public static function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Standard error envelope: { "message": "...", "errors": { ... } }.
     *
     * The `errors` key is omitted when no field errors are provided.
     */
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
