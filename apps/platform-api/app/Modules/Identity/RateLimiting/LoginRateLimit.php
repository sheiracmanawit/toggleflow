<?php

declare(strict_types=1);

namespace App\Modules\Identity\RateLimiting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class LoginRateLimit
{
    public const NAME = 'login';

    public const MAX_ATTEMPTS = 5;

    public static function key(Request $request): string
    {
        return self::keyFor(
            (string) $request->input('email'),
            (string) $request->ip(),
        );
    }

    public static function clear(Request $request): void
    {
        RateLimiter::clear(self::storageKeyFor(
            (string) $request->input('email'),
            (string) $request->ip(),
        ));
    }

    public static function clearFor(string $email, string $ip): void
    {
        RateLimiter::clear(self::storageKeyFor($email, $ip));
    }

    public static function attemptsFor(string $email, string $ip): int
    {
        return RateLimiter::attempts(self::storageKeyFor($email, $ip));
    }

    private static function keyFor(string $email, string $ip): string
    {
        $normalizedEmail = Str::lower(trim($email));

        return hash('sha256', $normalizedEmail).'|'.$ip;
    }

    private static function storageKeyFor(string $email, string $ip): string
    {
        // Laravel's named ThrottleRequests middleware hashes the limiter name and segment key.
        return md5(self::NAME.self::keyFor($email, $ip));
    }
}
