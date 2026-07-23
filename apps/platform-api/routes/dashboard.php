<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Auth\SessionController;
use App\Http\Controllers\Dashboard\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/session', [SessionController::class, 'store'])->name('session.store');

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
});

Route::fallback(static fn () => response()->json([
    'message' => 'The requested dashboard resource was not found.',
], 404))->name('not-found');
