<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Data;

use App\Modules\ReleaseManagement\Models\ApiKey;

final readonly class IssuedEnvironmentKey
{
    public function __construct(
        public ApiKey $apiKey,
        public string $credential,
    ) {}
}
