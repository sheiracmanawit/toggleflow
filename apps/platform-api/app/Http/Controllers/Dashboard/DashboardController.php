<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\GetDashboardSummary;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardSummaryResource;
use App\Models\User;

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
