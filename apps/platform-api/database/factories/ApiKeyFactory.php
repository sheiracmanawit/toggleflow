<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\ReleaseManagement\Models\ApiKey;
use App\Modules\ReleaseManagement\Models\Environment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<ApiKey> */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'name' => fake()->unique()->words(3, true),
            'prefix' => bin2hex(random_bytes(8)),
            'secret_hash' => Hash::make(bin2hex(random_bytes(32))),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
