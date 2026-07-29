<?php

declare(strict_types=1);

namespace App\Actions\FeatureFlags;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Models\Environment;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SetEnvironmentFlagState
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(FeatureFlag $flag, Environment $environment, User $actor, bool $enabled): FeatureFlag
    {
        return DB::transaction(function () use ($flag, $environment, $actor, $enabled): FeatureFlag {
            $project = Project::query()
                ->active()
                ->lockForUpdate()
                ->find($flag->project_id);
            if (! $project instanceof Project) {
                throw new AuthorizationException;
            }

            $lockedFlag = $project->featureFlags()
                ->active()
                ->lockForUpdate()
                ->find($flag->id);
            if (! $lockedFlag instanceof FeatureFlag) {
                throw new AuthorizationException;
            }

            /** @var EnvironmentFlag|null $state */
            $state = EnvironmentFlag::query()
                ->where('feature_flag_id', $lockedFlag->id)
                ->where('environment_id', $environment->id)
                ->lockForUpdate()
                ->first();

            if ($state === null) {
                throw new RuntimeException('The environment flag configuration is missing.');
            }

            if ($state->enabled !== $enabled) {
                $before = $state->enabled;
                $state->update(['enabled' => $enabled]);

                $this->recordAuditEvent->record(
                    $lockedFlag,
                    $actor,
                    $enabled ? AuditEventAction::FeatureFlagEnabled : AuditEventAction::FeatureFlagDisabled,
                    [
                        'environment' => [
                            'id' => $environment->id,
                            'key' => $environment->key,
                            'name' => $environment->name,
                        ],
                        'before' => ['enabled' => $before],
                        'after' => ['enabled' => $enabled],
                    ],
                );
            }

            return $lockedFlag->refresh()->load('environmentStates.environment');
        });
    }
}
