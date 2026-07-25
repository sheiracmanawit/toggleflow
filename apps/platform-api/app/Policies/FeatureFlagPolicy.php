<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\FeatureFlagStatus;
use App\Enums\ProjectStatus;
use App\Models\FeatureFlag;
use App\Models\User;

class FeatureFlagPolicy
{
    public function view(User $user, FeatureFlag $flag): bool
    {
        return $flag->project->owner_id === $user->id;
    }

    public function update(User $user, FeatureFlag $flag): bool
    {
        return $this->view($user, $flag)
            && $flag->project->statusValue() === ProjectStatus::Active
            && $flag->statusValue() === FeatureFlagStatus::Active;
    }

    public function archive(User $user, FeatureFlag $flag): bool
    {
        return $this->view($user, $flag) && $flag->project->statusValue() === ProjectStatus::Active;
    }
}
