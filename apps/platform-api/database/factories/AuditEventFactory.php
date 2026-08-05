<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditEvent> */
class AuditEventFactory extends Factory
{
    protected $model = AuditEvent::class;

    public function definition(): array
    {
        $project = Project::factory();

        return [
            'project_id' => $project,
            'actor_id' => User::factory(),
            'action' => AuditEventAction::ProjectCreated,
            'subject_type' => Project::class,
            'subject_id' => fn (array $attributes): int => (int) $attributes['project_id'],
            'metadata' => [
                'project' => ['name' => fake()->company()],
                'subject' => ['name' => fake()->company()],
                'actor' => ['name' => fake()->name()],
            ],
        ];
    }
}
