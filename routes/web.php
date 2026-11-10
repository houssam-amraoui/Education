<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PrototypeController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StartPreviewSession;
use App\Support\Prototype;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// Keep the fixture catalogue separate from live routes.
if (app()->environment(['local', 'testing'])) {
    Route::withoutMiddleware(StartSession::class)
        ->middleware([StartPreviewSession::class, SetLocale::class])
        ->group(function (): void {
            Route::post('/locale', LocaleController::class)->name('locale.update');

            foreach (Prototype::screens() as $screen) {
                Route::get($screen['path'], [PrototypeController::class, 'show'])
                    ->defaults('screen', $screen['id'])
                    ->name(Prototype::routeName($screen['id']));
            }
        });
}
