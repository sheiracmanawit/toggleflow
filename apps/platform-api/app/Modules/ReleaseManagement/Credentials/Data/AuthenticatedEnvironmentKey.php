<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Data;

final readonly class AuthenticatedEnvironmentKey
{
    public function __construct(
        public int $credentialId,
        public int $environmentId,
        public int $projectId,
    ) {}
}
