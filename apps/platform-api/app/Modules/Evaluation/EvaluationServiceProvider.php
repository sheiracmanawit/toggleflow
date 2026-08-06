<?php

declare(strict_types=1);

namespace App\Modules\Evaluation;

use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use App\Modules\Evaluation\Http\Responses\EvaluationErrorResponse;
use App\Modules\Evaluation\RateLimiting\EvaluationRateLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

final class EvaluationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for(EvaluationRateLimit::INVALID_NAME, function (Request $request): Limit {
            return Limit::perMinute(EvaluationRateLimit::MAX_ATTEMPTS)
                ->by(EvaluationRateLimit::invalidKey($request))
                ->after(
                    static fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED,
                )
                ->response(static fn (Request $request, array $headers) => EvaluationErrorResponse::make(
                    EvaluationErrorCode::RateLimited,
                    $headers,
                ));
        });

        RateLimiter::for(EvaluationRateLimit::NAME, function (Request $request): Limit|Unlimited {
            $key = EvaluationRateLimit::authenticatedKey($request);
            if ($key === null) {
                return Limit::none();
            }

            return Limit::perMinute(EvaluationRateLimit::MAX_ATTEMPTS)
                ->by($key)
                ->response(static fn (Request $request, array $headers) => EvaluationErrorResponse::make(
                    EvaluationErrorCode::RateLimited,
                    $headers,
                ));
        });

        Route::middleware('api')
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group(__DIR__.'/routes.php');
    }
}
