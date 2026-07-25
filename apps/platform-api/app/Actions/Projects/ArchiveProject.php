<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\AuditEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ArchiveProject
{
    public function execute(Project $project, User $actor): Project
    {
        if ($project->statusValue() === ProjectStatus::Archived) {
            return $project->load('environments');
        }

        return DB::transaction(function () use ($project, $actor): Project {
            $project->forceFill(['status' => ProjectStatus::Archived])->save();

            AuditEvent::query()->create([
                'project_id' => $project->id,
                'actor_id' => $actor->id,
                'action' => 'project.archived',
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'metadata' => [
                    'before' => ['status' => ProjectStatus::Active->value],
                    'after' => ['status' => ProjectStatus::Archived->value],
                ],
            ]);

            return $project->refresh()->load('environments');
        });
    }
}
