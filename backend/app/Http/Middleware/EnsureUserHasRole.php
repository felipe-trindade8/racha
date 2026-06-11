<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users holding one of the given roles.
 *
 * Usage: `Route::middleware('role:administrator')` or
 * `Route::middleware('role:administrator,player')` for several roles.
 *
 * Unauthenticated requests yield a 401 and authenticated users without an
 * allowed role yield a 403; both are rendered through the standard error
 * envelope by the global exception handler.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        if (! in_array($user->role->value, $roles, true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return $next($request);
    }
}
