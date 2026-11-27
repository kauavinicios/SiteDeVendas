<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(function () {
            $rule = Password::min(8)->max(128);

            return $this->app->isProduction()
                ? $rule->letters()->numbers()->uncompromised()
                : $rule;
        });

        if ($this->app->isProduction() || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::before(function (User $user): ?bool {
            if (! $user->active) {
                return false;
            }

            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission),
            );
        }
    }
}
