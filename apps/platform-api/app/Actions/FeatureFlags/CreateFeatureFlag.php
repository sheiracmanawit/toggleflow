<?php

declare(strict_types=1);

namespace App\Actions\FeatureFlags;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\FeatureFlagAuditAction;
use App\Enums\FeatureFlagStatus;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CreateFeatureFlag
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    /** @param array{name: string, key: string, description?: string|null} $attributes */
    public function execute(Project $project, User $actor, array $attributes): FeatureFlag
    {
        try {
            return DB::transaction(function () use ($project, $actor, $attributes): FeatureFlag {
                $environments = $project->environments()->get();

                if ($environments->count() !== 3
                    || $environments->pluck('key')->all() !== ['development', 'staging', 'production']) {
                    throw new RuntimeException('The project default environments are incomplete.');
                }

                $flag = $project->featureFlags()->make($attributes);
                $flag->forceFill(['status' => FeatureFlagStatus::Active])->save();

                $flag->environmentStates()->createMany($environments->map(
                    fn ($environment): array => ['environment_id' => $environment->id, 'enabled' => false],
                )->all());

                $this->recordAuditEvent->forFeatureFlag($flag, $actor, FeatureFlagAuditAction::Created, [
                    'after' => [
                        'name' => $flag->name,
                        'key' => $flag->key,
                        'description' => $flag->description,
                        'status' => FeatureFlagStatus::Active->value,
                    ],
                ]);

                return $flag->load($this->relations());
            });
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            if (str_contains($message, 'feature_flags_project_key_unique')
                || str_contains($message, 'feature_flags.project_id, feature_flags.key')) {
                throw ValidationException::withMessages([
                    'key' => ['The key has already been taken for this project.'],
                ]);
            }

            throw $exception;
        }
    }

    /** @return list<string> */
    private function relations(): array
    {
        return ['environmentStates.environment'];
    }
}
