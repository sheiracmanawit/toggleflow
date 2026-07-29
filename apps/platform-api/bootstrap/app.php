<?php

use App\Enums\EvaluationErrorCode;
use App\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Http\Middleware\RejectEnvironmentApiKeyFromDashboard;
use App\Http\Middleware\ThrottleAuthenticatedEvaluationRequests;
use App\Http\Responses\EvaluationErrorResponse;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', RejectEnvironmentApiKeyFromDashboard::class])
                ->prefix('dashboard')
                ->name('dashboard.')
                ->group(base_path('routes/dashboard.php'));

            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->appendToPriorityList(
            ThrottleRequests::class,
            AuthenticateEnvironmentApiKey::class,
        );
        $middleware->appendToPriorityList(
            AuthenticateEnvironmentApiKey::class,
            ThrottleAuthenticatedEvaluationRequests::class,
        );
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            RejectEnvironmentApiKeyFromDashboard::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($request->is('dashboard/*')) {
                return response()->json([
                    'message' => 'The requested dashboard resource was not found.',
                ], 404);
            }
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/v1/*')
                && ! $exception instanceof HttpExceptionInterface
                && ! $exception instanceof HttpResponseException) {
                return EvaluationErrorResponse::make(EvaluationErrorCode::InternalError);
            }
        });
    })->create();
