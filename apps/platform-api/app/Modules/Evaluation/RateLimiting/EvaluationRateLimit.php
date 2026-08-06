<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\RateLimiting;

use App\Modules\Evaluation\Http\Middleware\AuthenticateEnvironmentApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class EvaluationRateLimit
{
    public const NAME = 'evaluation';

    public const INVALID_NAME = 'evaluation-invalid';

    public const MAX_ATTEMPTS = 120;

    public static function authenticatedKey(Request $request): ?string
    {
        $credential = AuthenticateEnvironmentApiKey::credential($request);

        return $credential !== null
            ? self::keyForCredentialId($credential->credentialId)
            : null;
    }

    public static function invalidKey(Request $request): string
    {
        return self::keyForInvalidIp((string) $request->ip());
    }

    public static function clearForCredentialId(int $credentialId): void
    {
        RateLimiter::clear(self::storageKey(self::NAME, self::keyForCredentialId($credentialId)));
    }

    public static function clearForInvalidIp(string $ip): void
    {
        RateLimiter::clear(self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip)));
    }

    public static function attemptsForCredentialId(int $credentialId): int
    {
        return RateLimiter::attempts(self::storageKey(self::NAME, self::keyForCredentialId($credentialId)));
    }

    public static function attemptsForInvalidIp(string $ip): int
    {
        return RateLimiter::attempts(self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip)));
    }

    public static function storageKeyForCredentialId(int $credentialId): string
    {
        return self::storageKey(self::NAME, self::keyForCredentialId($credentialId));
    }

    public static function storageKeyForInvalidIp(string $ip): string
    {
        return self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip));
    }

    private static function keyForCredentialId(int $credentialId): string
    {
        return 'api-key:'.$credentialId;
    }

    private static function keyForInvalidIp(string $ip): string
    {
        $packedIp = @inet_pton(trim($ip));
        $normalizedIp = $packedIp === false
            ? Str::lower(trim($ip))
            : bin2hex($packedIp);

        return 'invalid-ip:'.$normalizedIp;
    }

    private static function storageKey(string $limiterName, string $segment): string
    {
        // Laravel's named ThrottleRequests middleware hashes the limiter name and segment key.
        return md5($limiterName.$segment);
    }
}
