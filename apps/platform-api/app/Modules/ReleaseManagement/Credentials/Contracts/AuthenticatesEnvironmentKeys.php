<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Contracts;

use App\Modules\ReleaseManagement\Models\ApiKey;

interface AuthenticatesEnvironmentKeys
{
    public function authenticate(string $credential): ?ApiKey;
}
