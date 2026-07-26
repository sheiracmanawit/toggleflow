<?php

declare(strict_types=1);

namespace App\Actions\FeatureFlags;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AuditEventAction;
use App\Models\Environment;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SetEnvironmentFlagState
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(FeatureFlag $flag, Environment $environment, User $actor, bool $enabled): FeatureFlag
    {
        return DB::transaction(function () use ($flag, $environment, $actor, $enabled): FeatureFlag {
            /** @var EnvironmentFlag|null $state */
            $state = EnvironmentFlag::query()
                ->where('feature_flag_id', $flag->id)
                ->where('environment_id', $environment->id)
                ->lockForUpdate()
                ->first();

            if ($state === null) {
                throw new RuntimeException('The environment flag configuration is missing.');
            }

            if ($state->enabled !== $enabled) {
                $before = $state->enabled;
                $state->update(['enabled' => $enabled]);

                $this->recordAuditEvent->forFeatureFlag(
                    $flag,
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

            return $flag->refresh()->load('environmentStates.environment');
        });
    }
}
