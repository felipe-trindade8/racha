<?php

use App\Helpers\ApiResponse;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Normalize not-found errors (missing routes and missing models) into
        // the standard error envelope with an explicit message.
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request): ?JsonResponse {
            return $request->is('api/*')
                ? ApiResponse::error('Resource not found.', status: 404)
                : null;
        });
    })->create();
