<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Evaluation\EvaluateFeatureFlag;
use App\Enums\EvaluationErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Http\Resources\Api\V1\EvaluationResource;
use App\Http\Responses\EvaluationErrorResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EvaluationController extends Controller
{
    public function show(
        Request $request,
        string $flagKey,
        EvaluateFeatureFlag $evaluateFeatureFlag,
    ): EvaluationResource|JsonResponse {
        $error = AuthenticateEnvironmentApiKey::error($request);
        if ($error !== null) {
            return EvaluationErrorResponse::make($error);
        }

        $apiKey = AuthenticateEnvironmentApiKey::apiKey($request);
        if ($apiKey === null) {
            return EvaluationErrorResponse::make(EvaluationErrorCode::InvalidApiKey);
        }

        return new EvaluationResource(
            $evaluateFeatureFlag->evaluate($apiKey->environment, $flagKey),
        );
    }
}
