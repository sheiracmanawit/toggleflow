<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\AuditEvent;
use App\Models\FeatureFlag;
use App\Models\User;

final class RecordAuditEvent
{
    /** @param array<string, mixed> $metadata */
    public function forFeatureFlag(FeatureFlag $flag, User $actor, string $action, array $metadata): AuditEvent
    {
        return AuditEvent::query()->create([
            'project_id' => $flag->project_id,
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => FeatureFlag::class,
            'subject_id' => $flag->id,
            'metadata' => $metadata,
        ]);
    }
}
