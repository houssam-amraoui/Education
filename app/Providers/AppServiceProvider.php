<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use App\Policies\CurriculumPolicy;
use App\Support\Ability;
use App\Support\AccessTarget;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::morphMap(AccessTarget::MODELS);
        Gate::define(Ability::EnterAdministration->value, fn (User $user): bool => $user->hasAbility(Ability::EnterAdministration));
        Gate::define('rewards.manage', fn (User $user): bool => $user->isAdministrator()
            && $user->hasAbility(Ability::EnterAdministration));
        Gate::define('analytics.view', fn (User $user): bool => $user->isAdministrator()
            && $user->hasAbility(Ability::EnterAdministration));
        Gate::define('settings.manage', fn (User $user): bool => $user->isAdministrator()
            && $user->hasAbility(Ability::EnterAdministration));
        Gate::define('access-rules.manage', fn (User $user): bool => app(CurriculumPolicy::class)->manage($user));
        Gate::policy(Level::class, CurriculumPolicy::class);
        Gate::policy(Unit::class, CurriculumPolicy::class);
        Gate::policy(Category::class, CurriculumPolicy::class);
        Gate::policy(Lesson::class, CurriculumPolicy::class);
    }
}
