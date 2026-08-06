<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'status' => ProjectStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Archived]);
    }
}
