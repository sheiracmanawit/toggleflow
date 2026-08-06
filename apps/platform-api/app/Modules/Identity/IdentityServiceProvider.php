<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Identity\Http\Middleware\RejectEnvironmentApiKeyFromDashboard;
use App\Modules\Identity\RateLimiting\LoginRateLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

final class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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

        Route::middleware(['web', RejectEnvironmentApiKeyFromDashboard::class])
            ->prefix('dashboard')
            ->name('dashboard.')
            ->group(__DIR__.'/routes.php');
    }
}
