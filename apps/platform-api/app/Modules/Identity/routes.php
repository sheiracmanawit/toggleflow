<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\SessionController;
use App\Modules\Identity\RateLimiting\LoginRateLimit;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/session', [SessionController::class, 'store'])
        ->middleware(ThrottleRequests::using(LoginRateLimit::NAME))
        ->name('session.store');

    Route::get('/session', [SessionController::class, 'show'])
        ->middleware('auth:sanctum')
        ->name('session.show');
    Route::delete('/session', [SessionController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('session.destroy');
});
