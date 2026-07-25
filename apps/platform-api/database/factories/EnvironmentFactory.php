<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Environment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Environment> */
class EnvironmentFactory extends Factory
{
    protected $model = Environment::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(1);

        return [
            'project_id' => Project::factory(),
            'name' => ucfirst($key),
            'key' => $key,
            'color' => '#64748b',
            'position' => fake()->unique()->numberBetween(10, 250),
        ];
    }
}
