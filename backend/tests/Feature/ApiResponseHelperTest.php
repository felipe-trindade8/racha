<?php

use App\Helpers\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;

it('wraps success payloads under a data key', function (): void {
    $response = ApiResponse::success(['name' => 'Racha'], 201);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true))->toBe(['data' => ['name' => 'Racha']]);
});

it('builds a paginated envelope with data and meta', function (): void {
    $paginator = new LengthAwarePaginator(
        items: ['a', 'b'],
        total: 10,
        perPage: 2,
        currentPage: 1,
    );

    $response = ApiResponse::paginated($paginator);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'data' => ['a', 'b'],
            'meta' => [
                'current_page' => 1,
                'per_page' => 2,
                'total' => 10,
                'last_page' => 5,
            ],
        ]);
});

it('builds an error envelope with only a message by default', function (): void {
    $response = ApiResponse::error('Something went wrong.', status: 400);

    expect($response->getStatusCode())->toBe(400)
        ->and($response->getData(true))->toBe(['message' => 'Something went wrong.']);
});

it('includes the errors map when field errors are provided', function (): void {
    $response = ApiResponse::error('Validation failed.', ['name' => ['Required.']], 422);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'Validation failed.',
            'errors' => ['name' => ['Required.']],
        ]);
});
