<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\FoundationController as EvaluationFoundationController;
use App\Http\Controllers\Management\FoundationController as ManagementFoundationController;
use Illuminate\Support\Facades\Route;

Route::prefix('management')->name('management.')->group(function (): void {
    Route::get('/foundation', ManagementFoundationController::class)->name('foundation');
});

Route::prefix('v1')->name('evaluation.v1.')->group(function (): void {
    Route::get('/foundation', EvaluationFoundationController::class)->name('foundation');
});
