<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\FeatureFlag;

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
