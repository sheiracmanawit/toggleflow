<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ApiKeys\RecordEnvironmentKeyUsage;
use App\Contracts\AuthenticatesEnvironmentKeys;
use App\Enums\EvaluationErrorCode;
use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateEnvironmentApiKey
{
    private const API_KEY_ATTRIBUTE = 'toggleflow.evaluation.api_key';

    private const ERROR_ATTRIBUTE = 'toggleflow.evaluation.authentication_error';

    public function __construct(
        private readonly AuthenticatesEnvironmentKeys $authenticator,
        private readonly RecordEnvironmentKeyUsage $recordUsage,
    ) {}

    /**
     * Authentication records its result on the request so the named throttle
     * middleware can segment valid credentials by database ID and every invalid
     * credential by normalized client IP before the controller returns the error.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->headers->get('Authorization');

        if (! is_string($authorization) || trim($authorization) === '') {
            $request->attributes->set(self::ERROR_ATTRIBUTE, EvaluationErrorCode::MissingApiKey);

            return $next($request);
        }

        $credential = $request->bearerToken();
        if (! is_string($credential) || $credential === '') {
            $request->attributes->set(self::ERROR_ATTRIBUTE, EvaluationErrorCode::InvalidApiKey);

            return $next($request);
        }

        $apiKey = $this->authenticator->authenticate($credential);
        if (! $apiKey instanceof ApiKey) {
            $request->attributes->set(self::ERROR_ATTRIBUTE, EvaluationErrorCode::InvalidApiKey);

            return $next($request);
        }

        $this->recordUsage->execute($apiKey, CarbonImmutable::now());
        $request->attributes->set(self::API_KEY_ATTRIBUTE, $apiKey);

        return $next($request);
    }

    public static function apiKey(Request $request): ?ApiKey
    {
        $apiKey = $request->attributes->get(self::API_KEY_ATTRIBUTE);

        return $apiKey instanceof ApiKey ? $apiKey : null;
    }

    public static function error(Request $request): ?EvaluationErrorCode
    {
        $error = $request->attributes->get(self::ERROR_ATTRIBUTE);

        return $error instanceof EvaluationErrorCode ? $error : null;
    }
}
