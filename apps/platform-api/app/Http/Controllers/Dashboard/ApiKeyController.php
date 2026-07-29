<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\ApiKeys\IssueEnvironmentKey;
use App\Actions\ApiKeys\RevokeEnvironmentKey;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ApiKeys\StoreApiKeyRequest;
use App\Http\Resources\Dashboard\ApiKeyResource;
use App\Models\ApiKey;
use App\Models\Environment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiKeyController extends Controller
{
    public function index(int $project): AnonymousResourceCollection
    {
        $ownedProject = $this->ownedProject($project);
        $apiKeys = ApiKey::query()
            ->whereHas('environment', fn ($query) => $query->where('project_id', $ownedProject->id))
            ->with('environment')
            ->latest()
            ->get();

        return ApiKeyResource::collection($apiKeys);
    }

    public function store(
        StoreApiKeyRequest $request,
        int $project,
        int $environment,
        IssueEnvironmentKey $issueEnvironmentKey,
    ): JsonResponse {
        $ownedProject = $this->activeOwnedProject($project);
        $ownedEnvironment = $ownedProject->environments()->find($environment);
        if (! $ownedEnvironment instanceof Environment) {
            $this->notFound();
        }

        $issued = $issueEnvironmentKey->execute(
            $ownedEnvironment,
            $this->owner(),
            $request->string('name')->toString(),
        );

        return (new ApiKeyResource($issued->apiKey))
            ->additional(['credential' => $issued->credential])
            ->response()
            ->setStatusCode(201);
    }

    public function revoke(
        int $project,
        int $apiKey,
        RevokeEnvironmentKey $revokeEnvironmentKey,
    ): ApiKeyResource {
        $ownedProject = $this->activeOwnedProject($project);
        $ownedApiKey = ApiKey::query()
            ->whereKey($apiKey)
            ->whereHas('environment', fn ($query) => $query->where('project_id', $ownedProject->id))
            ->with('environment')
            ->first();
        if (! $ownedApiKey instanceof ApiKey) {
            $this->notFound();
        }

        return new ApiKeyResource($revokeEnvironmentKey->execute($ownedApiKey, $this->owner()));
    }

    private function owner(): User
    {
        /** @var User $owner */
        $owner = request()->user();

        return $owner;
    }

    private function ownedProject(int $projectId): Project
    {
        $project = $this->owner()->projects()->find($projectId);
        if (! $project instanceof Project) {
            $this->notFound();
        }

        return $project;
    }

    private function activeOwnedProject(int $projectId): Project
    {
        $project = $this->ownedProject($projectId);
        if ($project->statusValue() !== ProjectStatus::Active) {
            throw new AuthorizationException;
        }

        return $project;
    }

    private function notFound(): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The requested dashboard resource was not found.',
        ], 404));
    }
}
