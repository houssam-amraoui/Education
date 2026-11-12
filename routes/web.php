<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CurriculumPageController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Auth\AccountPageController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CurriculumController;
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

Route::prefix('admin/curriculum')->name('admin.curriculum.')
    ->middleware([SetLocale::class, 'auth:web', 'can:'.Ability::EnterAdministration->value])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('levels', [CurriculumPageController::class, 'levels'])->name('levels.index');
        Route::get('levels/create', [CurriculumPageController::class, 'createLevel'])->name('levels.create');
        Route::get('levels/{level}/edit', [CurriculumPageController::class, 'editLevel'])->whereNumber('level')->name('levels.edit');
        Route::get('units', [CurriculumPageController::class, 'units'])->name('units.index');
        Route::get('units/create', [CurriculumPageController::class, 'createUnit'])->name('units.create');
        Route::get('units/{unit}/edit', [CurriculumPageController::class, 'editUnit'])->whereNumber('unit')->name('units.edit');
        Route::get('categories', [CurriculumPageController::class, 'categories'])->name('categories.index');
        Route::get('categories/create', [CurriculumPageController::class, 'createCategory'])->name('categories.create');
        Route::get('categories/{category}/edit', [CurriculumPageController::class, 'editCategory'])->whereNumber('category')->name('categories.edit');
        Route::post('levels', [LevelController::class, 'store'])->name('levels.store');
        Route::put('levels/{level}', [LevelController::class, 'update'])->whereNumber('level')->name('levels.update');
        Route::patch('levels/{level}/status', [LevelController::class, 'status'])->whereNumber('level')->name('levels.status');
        Route::patch('levels/{level}/position', [LevelController::class, 'reorder'])->whereNumber('level')->name('levels.reorder');
        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::put('units/{unit}', [UnitController::class, 'update'])->whereNumber('unit')->name('units.update');
        Route::patch('levels/{level}/units/{unit}/status', [UnitController::class, 'status'])->whereNumber(['level', 'unit'])->name('units.status');
        Route::patch('levels/{level}/units/{unit}/position', [UnitController::class, 'reorder'])->whereNumber(['level', 'unit'])->name('units.reorder');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->whereNumber('category')->name('categories.update');
        Route::patch('units/{unit}/categories/{category}/archive', [CategoryController::class, 'archive'])
            ->whereNumber(['unit', 'category'])->can('manage', 'category')->name('categories.archive');
        Route::patch('units/{unit}/categories/{category}/position', [CategoryController::class, 'reorder'])->whereNumber(['unit', 'category'])->name('categories.reorder');
        Route::get('lessons', [CurriculumPageController::class, 'lessons'])->name('lessons.index');
        Route::get('categories/{category}/lessons/create', [CurriculumPageController::class, 'createLesson'])->whereNumber('category')->name('lessons.create');
        Route::get('categories/{category}/lessons/{lesson}/edit', [CurriculumPageController::class, 'editLesson'])->whereNumber(['category', 'lesson'])->name('lessons.edit');
        Route::get('categories/{category}/lessons/{lesson}/relationships', [CurriculumPageController::class, 'lessonRelationships'])->whereNumber(['category', 'lesson'])->name('lessons.relationships');
        Route::get('categories/{category}/lessons/{lesson}/preview', [CurriculumPageController::class, 'previewLesson'])->whereNumber(['category', 'lesson'])->name('lessons.preview');
        Route::post('categories/{category}/lessons', [LessonController::class, 'store'])->whereNumber('category')->name('lessons.store');
        Route::put('categories/{category}/lessons/{lesson}', [LessonController::class, 'update'])->whereNumber(['category', 'lesson'])->name('lessons.update');
        Route::put('categories/{category}/lessons/{lesson}/next-lessons', [LessonController::class, 'nextLessons'])->whereNumber(['category', 'lesson'])->name('lessons.next-lessons.update');
        Route::patch('categories/{category}/lessons/{lesson}/status', [LessonController::class, 'status'])->whereNumber(['category', 'lesson'])->name('lessons.status');
        Route::patch('categories/{category}/lessons/{lesson}/position', [LessonController::class, 'reorder'])->whereNumber(['category', 'lesson'])->name('lessons.reorder');
    });

Route::middleware(SetLocale::class)->name('curriculum.')->group(function (): void {
    Route::get('/home', [CurriculumController::class, 'home'])->name('home');
    Route::get('/learn/paths', [CurriculumController::class, 'paths'])->name('paths');
    Route::get('/learn/units', [CurriculumController::class, 'units'])->name('units');
    Route::get('/learn/paths/{level}', [CurriculumController::class, 'level'])->whereNumber('level')->name('level');
    Route::get('/learn/paths/{level}/units', [CurriculumController::class, 'units'])->whereNumber('level')->name('level.units');
    Route::get('/learn/paths/{level}/units/{unit}', [CurriculumController::class, 'unit'])
        ->whereNumber(['level', 'unit'])->name('unit');
    Route::get('/learn/paths/{level}/units/{unit}/categories/{category}', [CurriculumController::class, 'category'])
        ->whereNumber(['level', 'unit', 'category'])->name('category');
    Route::get('/learn/paths/{level}/units/{unit}/categories/{category}/lessons/{lesson}', [CurriculumController::class, 'lesson'])
        ->whereNumber(['level', 'unit', 'category', 'lesson'])->name('lesson');
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
    Route::redirect('/', '/home');
}
