<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\ApiKey;

interface AuthenticatesEnvironmentKeys
{
    public function authenticate(string $credential): ?ApiKey;
}
