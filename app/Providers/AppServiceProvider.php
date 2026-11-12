<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use App\Policies\CurriculumPolicy;
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
        Gate::policy(Level::class, CurriculumPolicy::class);
        Gate::policy(Unit::class, CurriculumPolicy::class);
        Gate::policy(Category::class, CurriculumPolicy::class);
        Gate::policy(Lesson::class, CurriculumPolicy::class);
    }
}
