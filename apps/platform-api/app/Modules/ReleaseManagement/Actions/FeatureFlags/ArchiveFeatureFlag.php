<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\FeatureFlags;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Audit\RecordAuditEvent;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ArchiveFeatureFlag
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(FeatureFlag $flag, User $actor): FeatureFlag
    {
        return DB::transaction(function () use ($flag, $actor): FeatureFlag {
            $project = Project::query()
                ->active()
                ->lockForUpdate()
                ->find($flag->project_id);
            if (! $project instanceof Project) {
                throw new AuthorizationException;
            }

            $lockedFlag = $project->featureFlags()
                ->lockForUpdate()
                ->find($flag->id);
            if (! $lockedFlag instanceof FeatureFlag) {
                throw new AuthorizationException;
            }

            if ($lockedFlag->statusValue() === FeatureFlagStatus::Archived) {
                return $lockedFlag->load('environmentStates.environment');
            }

            $lockedFlag->forceFill(['status' => FeatureFlagStatus::Archived])->save();
            $this->recordAuditEvent->record($lockedFlag, $actor, AuditEventAction::FeatureFlagArchived, [
                'before' => ['status' => FeatureFlagStatus::Active->value],
                'after' => ['status' => FeatureFlagStatus::Archived->value],
            ]);

            return $lockedFlag->refresh()->load('environmentStates.environment');
        });
    }
}
