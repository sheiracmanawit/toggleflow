<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Evaluation\EvaluationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class EvaluationResource extends JsonResource
{
    /**
     * @return array{key: string, value: bool, reason: string}
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof EvaluationResult) {
            throw new LogicException('EvaluationResource requires an EvaluationResult.');
        }

        return [
            'key' => $this->resource->key,
            'value' => $this->resource->value,
            'reason' => $this->resource->reason->value,
        ];
    }
}
