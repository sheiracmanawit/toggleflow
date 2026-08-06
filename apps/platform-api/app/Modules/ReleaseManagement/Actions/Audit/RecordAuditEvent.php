<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\Audit;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Audit\Contracts\Auditable;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Models\ApiKey;
use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;

final class RecordAuditEvent
{
    /** @param array<string, mixed> $metadata */
    public function record(
        Auditable $subject,
        User $actor,
        AuditEventAction $action,
        array $metadata,
    ): AuditEvent {
        $project = $subject instanceof Project
            ? $subject
            : Project::query()->findOrFail($subject->auditProjectId());

        return AuditEvent::query()->create([
            'project_id' => $subject->auditProjectId(),
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject->auditSubjectType(),
            'subject_id' => $subject->auditSubjectId(),
            'metadata' => array_merge([
                'project' => ['name' => $project->name],
                'subject' => ['name' => $this->subjectName($subject)],
                'actor' => ['name' => $actor->name],
            ], $metadata),
        ]);
    }

    private function subjectName(Auditable $subject): string
    {
        return match (true) {
            $subject instanceof Project => $subject->name,
            $subject instanceof FeatureFlag => $subject->name,
            $subject instanceof ApiKey => $subject->name,
            default => 'Resource',
        };
    }
}
