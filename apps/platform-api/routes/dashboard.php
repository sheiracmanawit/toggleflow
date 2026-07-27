<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Auth\SessionController;
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
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->whereNumber('project')
        ->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])
        ->whereNumber('project')
        ->name('projects.update');
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])
        ->whereNumber('project')
        ->name('projects.archive');
    Route::get('/projects/{project}/flags', [FeatureFlagController::class, 'index'])
        ->whereNumber('project')
        ->name('projects.flags.index');
    Route::post('/projects/{project}/flags', [FeatureFlagController::class, 'store'])
        ->whereNumber('project')
        ->name('projects.flags.store');
    Route::get('/projects/{project}/flags/{flag}', [FeatureFlagController::class, 'show'])
        ->whereNumber(['project', 'flag'])
        ->name('projects.flags.show');
    Route::patch('/projects/{project}/flags/{flag}', [FeatureFlagController::class, 'update'])
        ->whereNumber(['project', 'flag'])
        ->name('projects.flags.update');
    Route::post('/projects/{project}/flags/{flag}/archive', [FeatureFlagController::class, 'archive'])
        ->whereNumber(['project', 'flag'])
        ->name('projects.flags.archive');
    Route::put(
        '/projects/{project}/flags/{flag}/environments/{environment}',
        [FeatureFlagController::class, 'setState'],
    )->whereNumber(['project', 'flag', 'environment'])->name('projects.flags.state');
});

Route::fallback(static fn () => response()->json([
    'message' => 'The requested dashboard resource was not found.',
], 404))->name('not-found');
