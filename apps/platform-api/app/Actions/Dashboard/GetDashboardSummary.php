<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Actions\Audit\HydrateAuditEventDisplayContext;
use App\Data\DashboardSummary;
use App\Http\Resources\Dashboard\AuditEventResource;
use App\Models\AuditEvent;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class GetDashboardSummary
{
    private const PROJECT_LIMIT = 6;

    private const ACTIVITY_LIMIT = 8;

    public function __construct(private readonly HydrateAuditEventDisplayContext $hydrateDisplayContext) {}

    public function execute(User $owner): DashboardSummary
    {
        return new DashboardSummary(
            projectCount: $this->activeProjectCount($owner),
            activeFlagCount: $this->activeFlagCount($owner),
            productionEnabledCount: $this->productionEnabledCount($owner),
            projects: $this->projectSummaries($owner),
            recentActivity: $this->recentActivity($owner),
        );
    }

    private function activeProjectCount(User $owner): int
    {
        return Project::query()
            ->ownedBy($owner)
            ->active()
            ->count();
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     active_flag_count: int,
     *     production_enabled_count: int,
     *     updated_at: string
     * }>
     */
    private function projectSummaries(User $owner): array
    {
        return Project::query()
            ->ownedBy($owner)
            ->active()
            ->withCount([
                'activeFeatureFlags as active_flag_count',
                'productionEnabledFeatureFlags as production_enabled_count',
            ])
            ->latest('updated_at')
            ->limit(self::PROJECT_LIMIT)
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'active_flag_count' => (int) $project->getAttribute('active_flag_count'),
                'production_enabled_count' => (int) $project->getAttribute('production_enabled_count'),
                'updated_at' => $project->updated_at->toISOString(),
            ])
            ->all();
    }

    private function activeFlagCount(User $owner): int
    {
        return FeatureFlag::query()
            ->active()
            ->forActiveProjectsOwnedBy($owner)
            ->count();
    }

    private function productionEnabledCount(User $owner): int
    {
        return EnvironmentFlag::query()
            ->productionEnabledForActiveProjectsOwnedBy($owner)
            ->distinct('feature_flag_id')
            ->count('feature_flag_id');
    }

    /** @return list<array<string, mixed>> */
    private function recentActivity(User $owner): array
    {
        $events = AuditEvent::query()
            ->forOwner($owner)
            ->with(['actor:id,name', 'project:id,name', 'subject'])
            ->latest('created_at')
            ->latest('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get();

        $this->hydrateDisplayContext->execute($events);

        return $this->activityItems($events);
    }

    /**
     * @param  Collection<int, AuditEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function activityItems(Collection $events): array
    {
        return $events
            ->map(fn (AuditEvent $event): array => AuditEventResource::make($event)->resolve())
            ->values()
            ->all();
    }
}
