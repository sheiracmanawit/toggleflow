<?php

declare(strict_types=1);

use App\Enums\EvaluationErrorCode;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Http\Middleware\ThrottleAuthenticatedEvaluationRequests;
use App\Http\RateLimiting\EvaluationRateLimit;
use App\Http\Responses\EvaluationErrorResponse;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::get('/flags/{flagKey}', [EvaluationController::class, 'show'])
    ->middleware([
        ThrottleRequests::using(EvaluationRateLimit::INVALID_NAME),
        AuthenticateEnvironmentApiKey::class,
        ThrottleAuthenticatedEvaluationRequests::class,
    ])
    ->name('flags.show');

Route::fallback(
    static fn () => EvaluationErrorResponse::make(EvaluationErrorCode::EndpointNotFound),
)->name('not-found');
