<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

final class UpdateProject
{
    /** @param array{name: string, description?: string|null} $attributes */
    public function execute(Project $project, array $attributes): Project
    {
        return DB::transaction(function () use ($project, $attributes): Project {
            $project->update($attributes);

            return $project->refresh()->load('environments');
        });
    }
}
