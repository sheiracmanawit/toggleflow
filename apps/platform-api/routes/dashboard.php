<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\Auth\SessionController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\FeatureFlagController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\RateLimiting\LoginRateLimit;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/session', [SessionController::class, 'store'])
        ->middleware(ThrottleRequests::using(LoginRateLimit::NAME))
        ->name('session.store');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/session', [SessionController::class, 'show'])->name('session.show');
        Route::delete('/session', [SessionController::class, 'destroy'])->name('session.destroy');
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/summary', [DashboardController::class, 'show'])->name('summary');

    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])
            ->whereNumber('project')
            ->name('show');
        Route::patch('/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->name('update');
        Route::post('/{project}/archive', [ProjectController::class, 'archive'])
            ->whereNumber('project')
            ->name('archive');

        Route::get('/{project}/flags', [FeatureFlagController::class, 'index'])
            ->whereNumber('project')
            ->name('flags.index');
        Route::post('/{project}/flags', [FeatureFlagController::class, 'store'])
            ->whereNumber('project')
            ->name('flags.store');
        Route::get('/{project}/flags/{flag}', [FeatureFlagController::class, 'show'])
            ->whereNumber(['project', 'flag'])
            ->name('flags.show');
        Route::patch('/{project}/flags/{flag}', [FeatureFlagController::class, 'update'])
            ->whereNumber(['project', 'flag'])
            ->name('flags.update');
        Route::post('/{project}/flags/{flag}/archive', [FeatureFlagController::class, 'archive'])
            ->whereNumber(['project', 'flag'])
            ->name('flags.archive');
        Route::put(
            '/{project}/flags/{flag}/environments/{environment}',
            [FeatureFlagController::class, 'setState'],
        )->whereNumber(['project', 'flag', 'environment'])->name('flags.state');

        Route::get('/{project}/api-keys', [ApiKeyController::class, 'index'])
            ->whereNumber('project')
            ->name('api-keys.index');
        Route::post('/{project}/environments/{environment}/api-keys', [ApiKeyController::class, 'store'])
            ->whereNumber(['project', 'environment'])
            ->name('api-keys.store');
        Route::post('/{project}/api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
            ->whereNumber(['project', 'apiKey'])
            ->name('api-keys.revoke');
    });
});

Route::fallback(static fn () => response()->json([
    'message' => 'The requested dashboard resource was not found.',
], 404))->name('not-found');
