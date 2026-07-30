<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\EvaluationErrorCode;
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
