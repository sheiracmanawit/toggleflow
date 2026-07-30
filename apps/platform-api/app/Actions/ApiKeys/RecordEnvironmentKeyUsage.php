<?php

declare(strict_types=1);

namespace App\Actions\ApiKeys;

use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class RecordEnvironmentKeyUsage
{
    public function execute(ApiKey $apiKey, CarbonImmutable $usedAt): void
    {
        ApiKey::query()
            ->whereKey($apiKey->getKey())
            ->where(function (Builder $query) use ($usedAt): void {
                $query->whereNull('last_used_at')
                    ->orWhere('last_used_at', '<', $usedAt);
            })
            ->update(['last_used_at' => $usedAt]);

        $currentUsage = $apiKey->getAttribute('last_used_at');
        if (! $currentUsage instanceof CarbonImmutable || $currentUsage->isBefore($usedAt)) {
            $apiKey->setAttribute('last_used_at', $usedAt);
        }
    }
}
