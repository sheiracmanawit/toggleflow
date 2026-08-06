<?php

use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use App\Modules\Evaluation\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Modules\Evaluation\Http\Middleware\ThrottleAuthenticatedEvaluationRequests;
use App\Modules\Evaluation\Http\Responses\EvaluationErrorResponse;
use App\Modules\Identity\Http\Middleware\RejectEnvironmentApiKeyFromDashboard;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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
