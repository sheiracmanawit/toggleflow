<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use App\Enums\EvaluationReason;
use App\Enums\FeatureFlagStatus;
use App\Models\Environment;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;

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
