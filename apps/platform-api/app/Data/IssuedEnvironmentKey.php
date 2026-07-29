<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ApiKey;

final readonly class IssuedEnvironmentKey
{
    public function __construct(
        public ApiKey $apiKey,
        public string $credential,
    ) {}
}
