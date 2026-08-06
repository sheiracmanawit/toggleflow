<?php

declare(strict_types=1);

use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use App\Modules\Evaluation\Http\Controllers\EvaluationController;
use App\Modules\Evaluation\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Modules\Evaluation\Http\Middleware\ThrottleAuthenticatedEvaluationRequests;
use App\Modules\Evaluation\Http\Responses\EvaluationErrorResponse;
use App\Modules\Evaluation\RateLimiting\EvaluationRateLimit;
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
