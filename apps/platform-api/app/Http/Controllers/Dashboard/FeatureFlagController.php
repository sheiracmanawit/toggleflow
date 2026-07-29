<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\FeatureFlags\ArchiveFeatureFlag;
use App\Actions\FeatureFlags\CreateFeatureFlag;
use App\Actions\FeatureFlags\SetEnvironmentFlagState;
use App\Actions\FeatureFlags\UpdateFeatureFlag;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\FeatureFlags\SetEnvironmentFlagStateRequest;
use App\Http\Requests\Dashboard\FeatureFlags\StoreFeatureFlagRequest;
use App\Http\Requests\Dashboard\FeatureFlags\UpdateFeatureFlagRequest;
use App\Http\Resources\Dashboard\FeatureFlagResource;
use App\Models\Environment;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeatureFlagController extends Controller
{
    public function index(int $project): AnonymousResourceCollection
    {
        $ownedProject = $this->activeOwnedProject($project);
        $flags = $ownedProject->featureFlags()->active()->latest('updated_at')
            ->with('environmentStates.environment')->get();

        return FeatureFlagResource::collection($flags);
    }

    public function store(
        StoreFeatureFlagRequest $request,
        int $project,
        CreateFeatureFlag $createFeatureFlag,
    ): FeatureFlagResource {
        $ownedProject = $this->activeOwnedProject($project);

        return new FeatureFlagResource(
            $createFeatureFlag->execute($ownedProject, $this->owner(), $request->validated()),
        );
    }

    public function show(int $project, int $flag): FeatureFlagResource
    {
        $ownedProject = $this->ownedProject($project);
        $featureFlag = $this->flag($ownedProject, $flag);
        $this->authorizeFlag('view', $featureFlag);

        return new FeatureFlagResource($featureFlag->load('environmentStates.environment'));
    }

    public function update(
        UpdateFeatureFlagRequest $request,
        int $project,
        int $flag,
        UpdateFeatureFlag $updateFeatureFlag,
    ): FeatureFlagResource {
        $featureFlag = $this->flag($this->activeOwnedProject($project), $flag);
        $this->authorizeFlag('update', $featureFlag);

        return new FeatureFlagResource(
            $updateFeatureFlag->execute($featureFlag, $this->owner(), $request->validated()),
        );
    }

    public function archive(
        int $project,
        int $flag,
        ArchiveFeatureFlag $archiveFeatureFlag,
    ): FeatureFlagResource {
        $featureFlag = $this->flag($this->activeOwnedProject($project), $flag);
        $this->authorizeFlag('archive', $featureFlag);

        return new FeatureFlagResource($archiveFeatureFlag->execute($featureFlag, $this->owner()));
    }

    public function setState(
        SetEnvironmentFlagStateRequest $request,
        int $project,
        int $flag,
        int $environment,
        SetEnvironmentFlagState $setEnvironmentFlagState,
    ): FeatureFlagResource {
        $ownedProject = $this->activeOwnedProject($project);
        $featureFlag = $this->flag($ownedProject, $flag);
        $this->authorizeFlag('update', $featureFlag);
        $ownedEnvironment = $ownedProject->environments()->find($environment);
        if (! $ownedEnvironment instanceof Environment) {
            $this->notFound();
        }

        return new FeatureFlagResource($setEnvironmentFlagState->execute(
            $featureFlag,
            $ownedEnvironment,
            $this->owner(),
            $request->boolean('enabled'),
        ));
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

    private function flag(Project $project, int $flagId): FeatureFlag
    {
        $flag = $project->featureFlags()->find($flagId);
        if (! $flag instanceof FeatureFlag) {
            $this->notFound();
        }

        return $flag;
    }

    private function authorizeFlag(string $ability, FeatureFlag $flag): void
    {
        if (! $this->owner()->can($ability, $flag)) {
            throw new AuthorizationException;
        }
    }

    private function notFound(): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The requested dashboard resource was not found.',
        ], 404));
    }
}
