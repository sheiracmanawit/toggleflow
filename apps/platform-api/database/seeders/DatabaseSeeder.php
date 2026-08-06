<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Actions\FeatureFlags\CreateFeatureFlag;
use App\Modules\ReleaseManagement\Actions\Projects\CreateProject;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(CreateProject $createProject, CreateFeatureFlag $createFeatureFlag): void
    {
        if (! config('toggleflow.demo.enabled')) {
            return;
        }

        $owner = User::query()->updateOrCreate([
            'email' => config('toggleflow.demo.email'),
        ], [
            'name' => config('toggleflow.demo.name'),
            'password' => config('toggleflow.demo.password'),
        ]);

        $project = Project::query()->ownedBy($owner)->where('slug', 'checkout-service')->first();

        if ($project === null) {
            $project = $createProject->execute($owner, [
                'name' => 'Checkout Service',
                'slug' => 'checkout-service',
                'description' => 'Demonstrates environment-isolated checkout releases.',
            ]);
        }

        if (! $project->featureFlags()->where('key', 'new-checkout')->exists()) {
            $createFeatureFlag->execute($project, $owner, [
                'name' => 'New checkout',
                'key' => 'new-checkout',
                'description' => 'Controls the new checkout experience.',
            ]);
        }
    }
}
