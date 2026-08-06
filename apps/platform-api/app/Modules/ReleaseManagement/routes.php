<?php

declare(strict_types=1);

use App\Modules\ReleaseManagement\Http\Controllers\ApiKeyController;
use App\Modules\ReleaseManagement\Http\Controllers\AuditEventController;
use App\Modules\ReleaseManagement\Http\Controllers\DashboardController;
use App\Modules\ReleaseManagement\Http\Controllers\FeatureFlagController;
use App\Modules\ReleaseManagement\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/summary', [DashboardController::class, 'show'])->name('summary');

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

    Route::get('/projects/{project}/audit-events', [AuditEventController::class, 'index'])
        ->whereNumber('project')
        ->name('projects.audit-events.index');

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

    Route::get('/projects/{project}/api-keys', [ApiKeyController::class, 'index'])
        ->whereNumber('project')
        ->name('projects.api-keys.index');
    Route::post('/projects/{project}/environments/{environment}/api-keys', [ApiKeyController::class, 'store'])
        ->whereNumber(['project', 'environment'])
        ->name('projects.api-keys.store');
    Route::post('/projects/{project}/api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])
        ->whereNumber(['project', 'apiKey'])
        ->name('projects.api-keys.revoke');
});

Route::fallback(static fn () => response()->json([
    'message' => 'The requested dashboard resource was not found.',
], 404))->name('not-found');
