<?php

declare(strict_types=1);

namespace App\Modules\Evaluation\Http\Controllers;

use App\Core\Http\Controller;
use App\Modules\Evaluation\Actions\EvaluateFeatureFlag;
use App\Modules\Evaluation\Enums\EvaluationErrorCode;
use App\Modules\Evaluation\Http\Middleware\AuthenticateEnvironmentApiKey;
use App\Modules\Evaluation\Http\Resources\EvaluationResource;
use App\Modules\Evaluation\Http\Responses\EvaluationErrorResponse;
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
