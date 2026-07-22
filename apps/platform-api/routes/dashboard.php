<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Auth\DemoCredentialsController;
use App\Http\Controllers\Dashboard\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('/demo', DemoCredentialsController::class)->name('demo');
    Route::post('/session', [SessionController::class, 'store'])->name('session.store');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/session', [SessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [SessionController::class, 'destroy'])->name('session.destroy');
    });
});

Route::fallback(static fn () => response()->json([
    'message' => 'The requested dashboard resource was not found.',
], 404))->name('not-found');
