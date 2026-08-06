<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Data;

use App\Modules\Evaluation\Enums\EvaluationReason;

final readonly class EvaluationResult
{
    public function __construct(
        public string $key,
        public bool $value,
        public EvaluationReason $reason,
    ) {}
}
