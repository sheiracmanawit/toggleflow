<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Contracts\Auditable;
use App\Enums\AuditEventAction;
use App\Models\AuditEvent;
use App\Models\User;

final class RecordAuditEvent
{
    /** @param array<string, mixed> $metadata */
    public function record(
        Auditable $subject,
        User $actor,
        AuditEventAction $action,
        array $metadata,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'project_id' => $subject->auditProjectId(),
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject->auditSubjectType(),
            'subject_id' => $subject->auditSubjectId(),
            'metadata' => $metadata,
        ]);
    }
}
