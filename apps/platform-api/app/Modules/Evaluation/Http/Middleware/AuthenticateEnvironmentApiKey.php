<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Http\Middleware;

use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use App\Modules\ReleaseManagement\Credentials\Contracts\AuthenticatesEnvironmentKeys;
use App\Modules\ReleaseManagement\Credentials\Contracts\RecordsEnvironmentKeyUsage;
use App\Modules\ReleaseManagement\Credentials\Data\AuthenticatedEnvironmentKey;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateEnvironmentApiKey
{
    private const CREDENTIAL_ATTRIBUTE = 'toggleflow.evaluation.credential';

    private const ERROR_ATTRIBUTE = 'toggleflow.evaluation.authentication_error';

    public function __construct(
        private readonly AuthenticatesEnvironmentKeys $authenticator,
        private readonly RecordsEnvironmentKeyUsage $recordUsage,
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

        $authenticated = $this->authenticator->authenticate($credential);
        if (! $authenticated instanceof AuthenticatedEnvironmentKey) {
            $request->attributes->set(self::ERROR_ATTRIBUTE, EvaluationErrorCode::InvalidApiKey);

            return $next($request);
        }

        $this->recordUsage->record($authenticated->credentialId, CarbonImmutable::now());
        $request->attributes->set(self::CREDENTIAL_ATTRIBUTE, $authenticated);

        return $next($request);
    }

    public static function credential(Request $request): ?AuthenticatedEnvironmentKey
    {
        $credential = $request->attributes->get(self::CREDENTIAL_ATTRIBUTE);

        return $credential instanceof AuthenticatedEnvironmentKey ? $credential : null;
    }

    public static function error(Request $request): ?EvaluationErrorCode
    {
        $error = $request->attributes->get(self::ERROR_ATTRIBUTE);

        return $error instanceof EvaluationErrorCode ? $error : null;
    }
}
