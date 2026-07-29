<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Environment;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnvironmentFlag> */
class EnvironmentFlagFactory extends Factory
{
    protected $model = EnvironmentFlag::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'feature_flag_id' => FeatureFlag::factory(),
            'enabled' => false,
        ];
    }
}
