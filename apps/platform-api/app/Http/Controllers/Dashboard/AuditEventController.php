<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Audit\GetProjectAuditHistory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\AuditEventResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditEventController extends Controller
{
    public function index(Request $request, int $project, GetProjectAuditHistory $history): AnonymousResourceCollection
    {
        /** @var User $owner */
        $owner = $request->user();
        $ownedProject = $owner->projects()->find($project);

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
