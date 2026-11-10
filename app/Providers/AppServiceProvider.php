<?php

namespace App\Providers;

use App\Support\Locale;
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
        //
    }
}
