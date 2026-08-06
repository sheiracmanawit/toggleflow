<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\Credentials;

use App\Modules\ReleaseManagement\Credentials\Contracts\RecordsEnvironmentKeyUsage;
use App\Modules\ReleaseManagement\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class RecordEnvironmentKeyUsage implements RecordsEnvironmentKeyUsage
{
    public function record(int $credentialId, CarbonImmutable $usedAt): void
    {
        ApiKey::query()
            ->whereKey($credentialId)
            ->where(function (Builder $query) use ($usedAt): void {
                $query->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<', $usedAt);
            })
            ->update(['last_used_at' => $usedAt]);
    }
}
