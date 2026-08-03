<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Models\AuditEvent;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetProjectAuditHistory
{
    /** @return LengthAwarePaginator<int, AuditEvent> */
    public function execute(Project $project, int $page = 1): LengthAwarePaginator
    {
        return AuditEvent::query()
            ->where('project_id', $project->id)
            ->with(['actor:id,name', 'project:id,name', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(perPage: 20, page: max(1, $page));
    }
}
