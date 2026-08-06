<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeatureFlag> */
class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'project_id' => Project::factory(),
            'name' => ucfirst($name),
            'key' => str($name)->slug()->value(),
            'description' => fake()->optional()->sentence(),
            'status' => FeatureFlagStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => FeatureFlagStatus::Archived]);
    }
}
