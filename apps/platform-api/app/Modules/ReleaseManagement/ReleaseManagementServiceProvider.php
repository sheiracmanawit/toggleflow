<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement;

use App\Modules\Identity\Http\Middleware\RejectEnvironmentApiKeyFromDashboard;
use App\Modules\ReleaseManagement\Credentials\Authentication\EnvironmentKeyAuthenticator;
use App\Modules\ReleaseManagement\Credentials\Contracts\AuthenticatesEnvironmentKeys;
use App\Modules\ReleaseManagement\Models\ApiKey;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use App\Modules\ReleaseManagement\Policies\FeatureFlagPolicy;
use App\Modules\ReleaseManagement\Policies\ProjectPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ReleaseManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthenticatesEnvironmentKeys::class, EnvironmentKeyAuthenticator::class);
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'App\\Models\\ApiKey' => ApiKey::class,
            'App\\Models\\FeatureFlag' => FeatureFlag::class,
            'App\\Models\\Project' => Project::class,
        ]);

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(FeatureFlag::class, FeatureFlagPolicy::class);

        Route::middleware(['web', RejectEnvironmentApiKeyFromDashboard::class])
            ->prefix('dashboard')
            ->name('dashboard.')
            ->group(__DIR__.'/routes.php');
    }
}
