<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateProject
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    /** @param array{name: string, description?: string|null} $attributes */
    public function execute(Project $project, User $actor, array $attributes): Project
    {
        return DB::transaction(function () use ($project, $actor, $attributes): Project {
            $before = ['name' => $project->name, 'description' => $project->description];
            $project->update($attributes);
            $project->refresh();
            $after = ['name' => $project->name, 'description' => $project->description];

            if ($before !== $after) {
                $this->recordAuditEvent->record($project, $actor, AuditEventAction::ProjectUpdated, [
                    'before' => $before,
                    'after' => $after,
                ]);
            }

            return $project->load('environments');
        });
    }
}
