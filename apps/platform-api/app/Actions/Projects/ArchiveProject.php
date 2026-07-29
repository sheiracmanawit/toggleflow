<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
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
