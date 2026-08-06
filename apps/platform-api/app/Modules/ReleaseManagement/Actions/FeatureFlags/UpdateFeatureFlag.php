<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\FeatureFlags;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Audit\RecordAuditEvent;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use Illuminate\Support\Facades\DB;

final class UpdateFeatureFlag
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    /** @param array{name: string, description?: string|null} $attributes */
    public function execute(FeatureFlag $flag, User $actor, array $attributes): FeatureFlag
    {
        return DB::transaction(function () use ($flag, $actor, $attributes): FeatureFlag {
            $before = ['name' => $flag->name, 'description' => $flag->description];
            $flag->update($attributes);
            $flag->refresh();
            $after = ['name' => $flag->name, 'description' => $flag->description];

            if ($before !== $after) {
                $this->recordAuditEvent->record($flag, $actor, AuditEventAction::FeatureFlagUpdated, [
                    'before' => $before,
                    'after' => $after,
                ]);
            }

            return $flag->load('environmentStates.environment');
        });
    }
}
