<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\UpdateProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Projects\StoreProjectRequest;
use App\Http\Requests\Dashboard\Projects\UpdateProjectRequest;
use App\Http\Resources\Dashboard\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $owner = $this->owner();
        $projects = $owner->projects()
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
        $project = $this->owner()->projects()->find($projectId);

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
