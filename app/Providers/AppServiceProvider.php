<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Ability;
use App\Support\Locale;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['config']->set([
            'app.locale' => Locale::resolve($this->app->getLocale()),
            'app.fallback_locale' => Locale::fallback(),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define(Ability::EnterAdministration->value, fn (User $user): bool => $user->hasAbility(Ability::EnterAdministration));
    }
}
