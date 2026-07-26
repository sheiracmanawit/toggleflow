<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\AuditEventAction;
use App\Enums\ProjectStatus;
use App\Models\AuditEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateProject
{
    /** @param array{name: string, slug: string, description?: string|null} $attributes */
    public function execute(User $owner, array $attributes): Project
    {
        try {
            return DB::transaction(function () use ($owner, $attributes): Project {
                $project = $owner->projects()->make($attributes);
                $project->forceFill(['status' => ProjectStatus::Active])->save();

                $project->environments()->createMany([
                    ['name' => 'Development', 'key' => 'development', 'color' => '#2563eb', 'position' => 1],
                    ['name' => 'Staging', 'key' => 'staging', 'color' => '#b45309', 'position' => 2],
                    ['name' => 'Production', 'key' => 'production', 'color' => '#7c3aed', 'position' => 3],
                ]);

                AuditEvent::query()->create([
                    'project_id' => $project->id,
                    'actor_id' => $owner->id,
                    'action' => AuditEventAction::ProjectCreated,
                    'subject_type' => Project::class,
                    'subject_id' => $project->id,
                    'metadata' => [
                        'after' => [
                            'name' => $project->name,
                            'slug' => $project->slug,
                            'description' => $project->description,
                            'status' => ProjectStatus::Active->value,
                        ],
                    ],
                ]);

                return $project->load('environments');
            });
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            $isOwnerSlugConflict = str_contains($message, 'projects_owner_slug_unique')
                || str_contains($message, 'projects.owner_id, projects.slug');

            if ($isOwnerSlugConflict) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken for one of your projects.'],
                ]);
            }

            throw $exception;
        }
    }
}
