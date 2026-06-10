<?php

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->prefix('api')->group(function (): void {
        Route::post('test/validate', function (Request $request): void {
            $request->validate(['name' => ['required']]);
        });

        Route::get('test/unauthenticated', function (): void {
            throw new AuthenticationException;
        });

        Route::get('test/forbidden', function (): void {
            throw new AuthorizationException('This action is unauthorized.');
        });

        Route::get('test/missing-model', function (): void {
            throw (new ModelNotFoundException)->setModel(User::class);
        });
    });
});

it('returns a 422 envelope with message and errors for validation failures', function (): void {
    $this->postJson('/api/test/validate', [])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['name']]);
});

it('returns a 401 envelope for unauthenticated requests', function (): void {
    $this->getJson('/api/test/unauthenticated')
        ->assertStatus(401)
        ->assertJsonStructure(['message']);
});

it('returns a 403 envelope for forbidden requests', function (): void {
    $this->getJson('/api/test/forbidden')
        ->assertStatus(403)
        ->assertJsonStructure(['message']);
});

it('returns a 404 envelope for unknown routes', function (): void {
    $this->getJson('/api/v1/does-not-exist')
        ->assertStatus(404)
        ->assertExactJson(['message' => 'Resource not found.']);
});

it('returns a 404 envelope for missing models', function (): void {
    $this->getJson('/api/test/missing-model')
        ->assertStatus(404)
        ->assertExactJson(['message' => 'Resource not found.']);
});
