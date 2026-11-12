<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Auth\AccountPageController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PrototypeController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StartPreviewSession;
use App\Support\Ability;
use App\Support\Prototype;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(SetLocale::class)->group(function (): void {
    Route::get('/', [AccountPageController::class, 'home'])->middleware('auth:web')->name('home');
    Route::get('profile', [ProfileController::class, 'show'])->middleware('auth:web')->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->middleware('auth:web')->name('profile.update');
    Route::get('preferences', [ProfileController::class, 'preferences'])->middleware('auth:web')->name('preferences.show');
    Route::put('preferences', [ProfileController::class, 'updatePreferences'])->middleware('auth:web')->name('preferences.update');
    Route::post('locale', [AccountPageController::class, 'locale'])->name('locale');
    Route::middleware('guest:web')->group(function (): void {
        Route::get('register', [AccountPageController::class, 'register'])->name('register.show');
        Route::post('register', RegisterController::class)->name('register');
        Route::get('login', [AccountPageController::class, 'login'])->name('login.show');
        Route::post('login', [SessionController::class, 'store'])->name('login');
    });
    Route::post('logout', [SessionController::class, 'destroy'])->middleware('auth:web')->name('logout');
});

Route::prefix('admin/accounts')->name('admin.accounts.')
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::put('users/{user}/roles', UserRoleController::class)->whereNumber('user')->name('users.roles.update');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->whereNumber('role')->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
    });

// Keep the fixture catalogue separate from live routes.
if (app()->environment(['local', 'testing'])) {
    Route::withoutMiddleware(StartSession::class)
        ->middleware([StartPreviewSession::class, SetLocale::class])
        ->group(function (): void {
            Route::post('/locale', LocaleController::class)->name('locale.update');
            Route::view('/prototype/tokens', 'prototype.fixtures.tokens')->name('prototype.tokens');
            Route::get('/prototype/primitives', [PrototypeController::class, 'primitives'])->name('prototype.primitives');

            foreach (Prototype::screens() as $screen) {
                Route::get($screen['path'], [PrototypeController::class, 'show'])
                    ->defaults('screen', $screen['id'])
                    ->name(Prototype::routeName($screen['id']));
            }
        });
} else {
    Route::redirect('/', '/account/login');
}
