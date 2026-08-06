<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\Audit;

use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetProjectAuditHistory
{
    public function __construct(private readonly HydrateAuditEventDisplayContext $hydrateDisplayContext) {}

    /** @return LengthAwarePaginator<int, AuditEvent> */
    public function execute(Project $project, int $page = 1): LengthAwarePaginator
    {
        $events = AuditEvent::query()
            ->where('project_id', $project->id)
            ->with(['actor:id,name', 'project:id,name', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: 20, page: max(1, $page));

        $this->hydrateDisplayContext->execute($events->getCollection());

        return $events;
    }
}
