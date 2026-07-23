<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

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
