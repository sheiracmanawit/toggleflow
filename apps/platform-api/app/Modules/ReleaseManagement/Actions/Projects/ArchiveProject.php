<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\Projects;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Audit\RecordAuditEvent;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Support\Facades\DB;

final class ArchiveProject
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(Project $project, User $actor): Project
    {
        if ($project->statusValue() === ProjectStatus::Archived) {
            return $project->load('environments');
        }

        return DB::transaction(function () use ($project, $actor): Project {
            $project->forceFill(['status' => ProjectStatus::Archived])->save();

            $this->recordAuditEvent->record(
                $project,
                $actor,
                AuditEventAction::ProjectArchived,
                [
                    'before' => ['status' => ProjectStatus::Active->value],
                    'after' => ['status' => ProjectStatus::Archived->value],
                ],
            );

            return $project->refresh()->load('environments');
        });
    }
}
