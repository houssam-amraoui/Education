<?php

use App\Http\Controllers\PrototypeController;
use App\Support\Prototype;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Visual-only GET pages: no authentication, sessions, database, or write endpoints.
foreach (Prototype::screens() as $screen) {
    Route::get($screen['path'], [PrototypeController::class, 'show'])
        ->defaults('screen', $screen['id'])
        ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class])
        ->name('prototype.'.$screen['id']);
}
