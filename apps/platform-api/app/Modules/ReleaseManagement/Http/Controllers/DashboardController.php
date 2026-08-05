<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Controllers;

use App\Core\Http\Controller;
use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\Dashboard\GetDashboardSummary;
use App\Modules\ReleaseManagement\Http\Resources\DashboardSummaryResource;

final class DashboardController extends Controller
{
    public function show(GetDashboardSummary $getDashboardSummary): DashboardSummaryResource
    {
        return new DashboardSummaryResource($getDashboardSummary->execute($this->owner()));
    }

    private function owner(): User
    {
        /** @var User $owner */
        $owner = request()->user();

        return $owner;
    }
}
