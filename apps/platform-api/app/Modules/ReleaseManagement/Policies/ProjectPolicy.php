<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\Project;

class ProjectPolicy
{
    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $project->statusValue() === ProjectStatus::Active;
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }
}
