<?php

namespace App\Providers;

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
        Gate::before(function ($user, string $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            return null;
        });

        Gate::after(function ($user, string $ability, ?bool $result) {
            if ($result === null && method_exists($user, 'hasAbility')) {
                return $user->hasAbility($ability);
            }
            return $result;
        });
    }
}
