<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Contracts;

use App\Modules\ReleaseManagement\Credentials\Data\AuthenticatedEnvironmentKey;

interface AuthenticatesEnvironmentKeys
{
    public function authenticate(string $credential): ?AuthenticatedEnvironmentKey;
}
