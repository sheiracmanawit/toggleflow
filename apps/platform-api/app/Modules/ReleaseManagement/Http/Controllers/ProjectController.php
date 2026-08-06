<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Controllers;

use App\Core\Http\Controller;
use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Projects\ArchiveProject;
use App\Modules\ReleaseManagement\Actions\Projects\CreateProject;
use App\Modules\ReleaseManagement\Actions\Projects\UpdateProject;
use App\Modules\ReleaseManagement\Http\Requests\Projects\StoreProjectRequest;
use App\Modules\ReleaseManagement\Http\Requests\Projects\UpdateProjectRequest;
use App\Modules\ReleaseManagement\Http\Resources\ProjectResource;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $owner = $this->owner();
        $projects = Project::query()
            ->ownedBy($owner)
            ->active()
            ->latest('updated_at')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request, CreateProject $createProject): ProjectResource
    {
        $project = $createProject->execute($this->owner(), $request->validated());

        return new ProjectResource($project);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(int $project): ProjectResource
    {
        $ownedProject = $this->ownedProject($project);
        $this->authorizeProject('view', $ownedProject);

        return new ProjectResource($ownedProject->load('environments'));
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateProjectRequest $request,
        int $project,
        UpdateProject $updateProject,
    ): ProjectResource {
        $ownedProject = $this->ownedProject($project);
        $this->authorizeProject('update', $ownedProject);

        return new ProjectResource($updateProject->execute($ownedProject, $this->owner(), $request->validated()));
    }

    /**
     * @throws AuthorizationException
     */
    public function archive(int $project, ArchiveProject $archiveProject): ProjectResource
    {
        $owner = $this->owner();
        $ownedProject = $this->ownedProject($project);
        $this->authorizeProject('archive', $ownedProject);

        return new ProjectResource($archiveProject->execute($ownedProject, $owner));
    }

    private function owner(): User
    {
        /** @var User $owner */
        $owner = request()->user();

        return $owner;
    }

    private function ownedProject(int $projectId): Project
    {
        $project = Project::query()->ownedBy($this->owner())->find($projectId);

        if ($project === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'The requested dashboard resource was not found.',
            ], 404));
        }

        return $project;
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeProject(string $ability, Project $project): void
    {
        if (! $this->owner()->can($ability, $project)) {
            throw new AuthorizationException;
        }
    }
}
