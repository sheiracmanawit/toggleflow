<?php

declare(strict_types=1);

namespace App\Data;

final readonly class DashboardSummary
{
    /**
     * @param list<array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     active_flag_count: int,
     *     production_enabled_count: int,
     *     updated_at: string
     * }> $projects
     * @param  list<array<string, mixed>>  $recentActivity
     */
    public function __construct(
        public int $projectCount,
        public int $activeFlagCount,
        public int $productionEnabledCount,
        public array $projects,
        public array $recentActivity,
    ) {}
}
