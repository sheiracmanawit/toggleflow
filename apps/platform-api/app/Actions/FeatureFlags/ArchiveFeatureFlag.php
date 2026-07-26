<?php

declare(strict_types=1);

namespace App\Actions\FeatureFlags;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Enums\FeatureFlagStatus;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ArchiveFeatureFlag
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(FeatureFlag $flag, User $actor): FeatureFlag
    {
        if ($flag->statusValue() === FeatureFlagStatus::Archived) {
            return $flag->load('environmentStates.environment');
        }

        return DB::transaction(function () use ($flag, $actor): FeatureFlag {
            $flag->forceFill(['status' => FeatureFlagStatus::Archived])->save();
            $this->recordAuditEvent->record($flag, $actor, AuditEventAction::FeatureFlagArchived, [
                'before' => ['status' => FeatureFlagStatus::Active->value],
                'after' => ['status' => FeatureFlagStatus::Archived->value],
            ]);

            return $flag->refresh()->load('environmentStates.environment');
        });
    }
}
