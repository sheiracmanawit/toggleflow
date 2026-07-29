<?php

declare(strict_types=1);

namespace App\Actions\ApiKeys;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RevokeEnvironmentKey
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(ApiKey $apiKey, User $actor): ApiKey
    {
        return DB::transaction(function () use ($apiKey, $actor): ApiKey {
            $lockedKey = ApiKey::query()->with('environment')->lockForUpdate()->findOrFail($apiKey->id);

            if ($lockedKey->isRevoked()) {
                return $lockedKey;
            }

            $lockedKey->forceFill(['revoked_at' => now()])->save();

            $this->recordAuditEvent->record($lockedKey, $actor, AuditEventAction::ApiKeyRevoked, [
                'name' => $lockedKey->name,
                'prefix' => $lockedKey->prefix,
                'environment_id' => $lockedKey->environment_id,
            ]);

            return $lockedKey;
        });
    }
}
