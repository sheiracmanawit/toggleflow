<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Resources;

use App\Modules\ReleaseManagement\Dashboard\Data\DashboardSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DashboardSummaryResource extends JsonResource
{
    public function __construct(DashboardSummary $resource)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var DashboardSummary $summary */
        $summary = $this->resource;

        return [
            'project_count' => $summary->projectCount,
            'active_flag_count' => $summary->activeFlagCount,
            'production_enabled_count' => $summary->productionEnabledCount,
            'projects' => $summary->projects,
            'recent_activity' => $summary->recentActivity,
        ];
    }
}
