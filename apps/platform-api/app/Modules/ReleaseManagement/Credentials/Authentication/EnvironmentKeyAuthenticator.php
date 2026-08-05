<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Authentication;

use App\Modules\ReleaseManagement\Credentials\Contracts\AuthenticatesEnvironmentKeys;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\ApiKey;
use Illuminate\Support\Facades\Hash;

final class EnvironmentKeyAuthenticator implements AuthenticatesEnvironmentKeys
{
    private const DUMMY_SECRET = '0000000000000000000000000000000000000000000000000000000000000000';

    private static ?string $dummyHash = null;

    public function authenticate(string $credential): ?ApiKey
    {
        $prefix = null;
        $secret = self::DUMMY_SECRET;

        if (preg_match('/\Atf_env_([a-f0-9]{16})_([a-f0-9]{64})\z/D', $credential, $parts) === 1) {
            $prefix = $parts[1];
            $secret = $parts[2];
        }

        $apiKey = $prefix === null
            ? null
            : ApiKey::query()
                ->with('environment.project')
                ->where('prefix', $prefix)
                ->first();

        $secretMatches = Hash::check(
            $secret,
            $apiKey instanceof ApiKey ? $apiKey->secret_hash : self::dummyHash(),
        );

        if (! $apiKey instanceof ApiKey
            || ! $secretMatches
            || $apiKey->isRevoked()
            || $apiKey->environment->project->statusValue() !== ProjectStatus::Active) {
            return null;
        }

        return $apiKey;
    }

    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(self::DUMMY_SECRET);
    }
}
