<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Administrators bypass every gate and policy check. Feature epics only
        // need to author policy methods for the non-administrator (player)
        // rules; administrator access is granted here once and for all.
        Gate::before(function (User $user): ?bool {
            return $user->role === RoleEnum::Administrator ? true : null;
        });

        // Explicit ability for route- or controller-level administrator checks
        // that are not tied to a specific model policy.
        Gate::define('administrator', function (User $user): bool {
            return $user->role === RoleEnum::Administrator;
        });
    }
}
