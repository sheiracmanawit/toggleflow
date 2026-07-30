<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AuthenticatesEnvironmentKeys;
use App\Domain\Evaluation\EnvironmentKeyAuthenticator;
use App\Enums\EvaluationErrorCode;
use App\Http\RateLimiting\EvaluationRateLimit;
use App\Http\RateLimiting\LoginRateLimit;
use App\Http\Responses\EvaluationErrorResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticatesEnvironmentKeys::class, EnvironmentKeyAuthenticator::class);
    }

    /**
     * Bootstrap any application services.
     */
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

        RateLimiter::for(LoginRateLimit::NAME, function (Request $request): Limit {
            return Limit::perMinute(LoginRateLimit::MAX_ATTEMPTS)
                ->by(LoginRateLimit::key($request))
                ->after(
                    static fn (Response $response): bool => $response->getStatusCode() === Response::HTTP_UNAUTHORIZED,
                )
                ->response(static fn (Request $request, array $headers) => response()->json([
                    'message' => 'Too many sign-in attempts. Please try again later.',
                ], Response::HTTP_TOO_MANY_REQUESTS, $headers));
        });
    }
}
