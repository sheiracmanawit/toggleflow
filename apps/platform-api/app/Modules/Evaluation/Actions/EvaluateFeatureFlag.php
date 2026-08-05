<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Actions;

use App\Modules\Evaluation\Data\EvaluationResult;
use App\Modules\Evaluation\Enums\EvaluationReason;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Models\Environment;
use App\Modules\ReleaseManagement\Models\EnvironmentFlag;
use App\Modules\ReleaseManagement\Models\FeatureFlag;

final class EvaluateFeatureFlag
{
    public function evaluate(Environment $environment, string $flagKey): EvaluationResult
    {
        $flag = FeatureFlag::query()
            ->where('project_id', $environment->project_id)
            ->where('key', $flagKey)
            ->first();

        if (! $flag instanceof FeatureFlag) {
            return new EvaluationResult($flagKey, false, EvaluationReason::FlagNotFound);
        }

        if ($flag->statusValue() === FeatureFlagStatus::Archived) {
            return new EvaluationResult($flagKey, false, EvaluationReason::FlagArchived);
        }

        $state = EnvironmentFlag::query()
            ->where('environment_id', $environment->getKey())
            ->where('feature_flag_id', $flag->getKey())
            ->first();

        if (! $state instanceof EnvironmentFlag) {
            return new EvaluationResult($flagKey, false, EvaluationReason::ConfigurationMissing);
        }

        return new EvaluationResult($flagKey, $state->enabled, EvaluationReason::Static);
    }
}
