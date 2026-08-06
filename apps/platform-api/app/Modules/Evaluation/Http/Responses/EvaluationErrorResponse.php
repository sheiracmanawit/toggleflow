<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Http\Responses;

use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use Illuminate\Http\JsonResponse;

final class EvaluationErrorResponse
{
    /** @param array<string, string|int> $headers */
    public static function make(EvaluationErrorCode $error, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $error->value,
                'message' => $error->message(),
            ],
        ], $error->status(), $headers);
    }
}
