<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Controllers;

use App\Core\Http\Controller;
use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Audit\GetProjectAuditHistory;
use App\Modules\ReleaseManagement\Http\Resources\AuditEventResource;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditEventController extends Controller
{
    public function index(Request $request, int $project, GetProjectAuditHistory $history): AnonymousResourceCollection
    {
        /** @var User $owner */
        $owner = $request->user();
        $ownedProject = Project::query()->ownedBy($owner)->find($project);

        if (! $ownedProject instanceof Project || ! $owner->can('view', $ownedProject)) {
            throw new HttpResponseException(response()->json([
                'message' => 'The requested dashboard resource was not found.',
            ], 404));
        }

        return AuditEventResource::collection(
            $history->execute($ownedProject, $request->integer('page', 1)),
        );
    }
}
