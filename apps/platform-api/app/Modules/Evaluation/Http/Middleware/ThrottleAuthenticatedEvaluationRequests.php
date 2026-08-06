<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Http\Middleware;

use App\Modules\Evaluation\RateLimiting\EvaluationRateLimit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleAuthenticatedEvaluationRequests
{
    public function __construct(private readonly ThrottleRequests $throttleRequests) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->throttleRequests->handle(
            $request,
            $next,
            EvaluationRateLimit::NAME,
        );
    }
}
