<?php

declare(strict_types=1);

namespace App\Actions\FeatureFlags;

use App\Actions\Audit\RecordAuditEvent;
use App\Models\FeatureFlag;
use App\Models\User;
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
                $this->recordAuditEvent->forFeatureFlag($flag, $actor, 'feature_flag.updated', [
                    'before' => $before,
                    'after' => $after,
                ]);
            }

            return $flag->load('environmentStates.environment');
        });
    }
}
