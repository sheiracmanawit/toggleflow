<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Credentials\Contracts;

use Carbon\CarbonImmutable;

interface RecordsEnvironmentKeyUsage
{
    public function record(int $credentialId, CarbonImmutable $usedAt): void;
}
