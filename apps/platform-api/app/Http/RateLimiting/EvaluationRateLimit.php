<?php

declare(strict_types=1);

namespace App\Http\RateLimiting;

use App\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Models\ApiKey;
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
        $apiKey = AuthenticateEnvironmentApiKey::apiKey($request);

        return $apiKey instanceof ApiKey
            ? self::keyForApiKey($apiKey)
            : null;
    }

    public static function invalidKey(Request $request): string
    {
        return self::keyForInvalidIp((string) $request->ip());
    }

    public static function clearForApiKey(ApiKey $apiKey): void
    {
        RateLimiter::clear(self::storageKey(self::NAME, self::keyForApiKey($apiKey)));
    }

    public static function clearForInvalidIp(string $ip): void
    {
        RateLimiter::clear(self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip)));
    }

    public static function attemptsForApiKey(ApiKey $apiKey): int
    {
        return RateLimiter::attempts(self::storageKey(self::NAME, self::keyForApiKey($apiKey)));
    }

    public static function attemptsForInvalidIp(string $ip): int
    {
        return RateLimiter::attempts(self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip)));
    }

    public static function storageKeyForApiKey(ApiKey $apiKey): string
    {
        return self::storageKey(self::NAME, self::keyForApiKey($apiKey));
    }

    public static function storageKeyForInvalidIp(string $ip): string
    {
        return self::storageKey(self::INVALID_NAME, self::keyForInvalidIp($ip));
    }

    private static function keyForApiKey(ApiKey $apiKey): string
    {
        return 'api-key:'.$apiKey->getKey();
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
