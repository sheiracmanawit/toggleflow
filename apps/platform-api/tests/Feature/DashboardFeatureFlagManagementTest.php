<?php

declare(strict_types=1);

use App\Enums\FeatureFlagStatus;
use App\Models\AuditEvent;
use App\Models\Environment;
use App\Models\EnvironmentFlag;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException as AuditStorageFailure;

uses(RefreshDatabase::class);

function projectWithEnvironments(User $owner): Project
{
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->environments()->createMany([
        ['name' => 'Development', 'key' => 'development', 'color' => '#2563eb', 'position' => 1],
        ['name' => 'Staging', 'key' => 'staging', 'color' => '#b45309', 'position' => 2],
        ['name' => 'Production', 'key' => 'production', 'color' => '#7c3aed', 'position' => 3],
    ]);

    return $project;
}

it('requires authentication for all flag management operations', function (): void {
    $project = projectWithEnvironments(User::factory()->create());
    $flag = FeatureFlag::factory()->for($project)->create();
    $environment = $project->environments()->firstOrFail();

    $this->getJson("/dashboard/projects/{$project->id}/flags")->assertUnauthorized();
    $this->postJson("/dashboard/projects/{$project->id}/flags", [])->assertUnauthorized();
    $this->getJson("/dashboard/projects/{$project->id}/flags/{$flag->id}")->assertUnauthorized();
    $this->patchJson("/dashboard/projects/{$project->id}/flags/{$flag->id}", [])->assertUnauthorized();
    $this->postJson("/dashboard/projects/{$project->id}/flags/{$flag->id}/archive")->assertUnauthorized();
    $this->putJson(
        "/dashboard/projects/{$project->id}/flags/{$flag->id}/environments/{$environment->id}",
        ['enabled' => true],
    )->assertUnauthorized();
});

it('creates a flag with three disabled states and a safe audit event atomically', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);

    $this->actingAs($owner)->postJson("/dashboard/projects/{$project->id}/flags", [
        'name' => ' New Checkout ',
        'key' => ' New Checkout ',
        'description' => ' Release checkout safely. ',
        'project_id' => 999,
        'status' => 'archived',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'New Checkout')
        ->assertJsonPath('data.key', 'new-checkout')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.environment_states.0.enabled', false)
        ->assertJsonPath('data.environment_states.1.enabled', false)
        ->assertJsonPath('data.environment_states.2.enabled', false)
        ->assertJsonMissingPath('data.owner_id');

    $flag = FeatureFlag::query()->sole();
    expect($flag->project_id)->toBe($project->id)
        ->and($flag->environmentStates()->count())->toBe(3)
        ->and($flag->environmentStates()->where('enabled', true)->count())->toBe(0);

    $event = AuditEvent::query()->where('action', 'feature_flag.created')->sole();
    expect($event->metadata)->toHaveKeys(['after'])
        ->and(json_encode($event->metadata))->not->toContain('project_id');
});

it('rejects invalid and duplicate project scoped keys without leaking across owners', function (): void {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $otherProject = projectWithEnvironments($otherOwner);
    FeatureFlag::factory()->for($project)->create(['key' => 'checkout']);

    $this->actingAs($owner)->postJson("/dashboard/projects/{$project->id}/flags", [
        'name' => '',
        'key' => '',
        'description' => str_repeat('x', 1001),
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'key', 'description']);

    $this->actingAs($owner)->postJson("/dashboard/projects/{$project->id}/flags", [
        'name' => 'Duplicate checkout',
        'key' => 'checkout',
    ])->assertUnprocessable()->assertJsonValidationErrors(['key']);

    $this->actingAs($otherOwner)->postJson("/dashboard/projects/{$otherProject->id}/flags", [
        'name' => 'Checkout',
        'key' => 'checkout',
    ])->assertCreated();

    $missing = $this->actingAs($otherOwner)->postJson('/dashboard/projects/999999/flags', [
        'name' => 'Hidden',
        'key' => 'checkout',
    ]);
    $foreign = $this->actingAs($otherOwner)->postJson("/dashboard/projects/{$project->id}/flags", [
        'name' => 'Hidden',
        'key' => 'checkout',
    ]);
    $foreign->assertStatus($missing->getStatusCode())->assertExactJson($missing->json());
});

it('rolls back flag creation when environments are incomplete or audit storage fails', function (): void {
    $owner = User::factory()->create();
    $incomplete = Project::factory()->for($owner, 'owner')->create();
    Environment::factory()->for($incomplete)->create(['name' => 'Development', 'key' => 'development', 'position' => 1]);

    $this->actingAs($owner)->postJson("/dashboard/projects/{$incomplete->id}/flags", [
        'name' => 'Checkout',
        'key' => 'checkout',
    ])->assertServerError();
    expect(FeatureFlag::query()->count())->toBe(0)->and(EnvironmentFlag::query()->count())->toBe(0);

    $project = projectWithEnvironments($owner);
    $dispatcher = AuditEvent::getEventDispatcher();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));
    try {
        $this->actingAs($owner)->postJson("/dashboard/projects/{$project->id}/flags", [
            'name' => 'Checkout',
            'key' => 'checkout',
        ])->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }
    expect(FeatureFlag::query()->count())->toBe(0)->and(EnvironmentFlag::query()->count())->toBe(0);
});

it('lists active owned flags in environment order and safely hides foreign resources', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $active = FeatureFlag::factory()->for($project)->create(['name' => 'Active']);
    FeatureFlag::factory()->for($project)->archived()->create(['name' => 'Archived']);
    foreach ($project->environments as $environment) {
        EnvironmentFlag::factory()->for($active)->for($environment)->create();
    }

    $this->actingAs($owner)->getJson("/dashboard/projects/{$project->id}/flags")
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active')
        ->assertJsonPath('data.0.environment_states.0.environment.key', 'development')
        ->assertJsonPath('data.0.environment_states.2.environment.key', 'production');

    $otherOwner = User::factory()->create();
    $otherProject = projectWithEnvironments($otherOwner);
    $otherFlag = FeatureFlag::factory()->for($otherProject)->create();
    $missing = $this->actingAs($owner)->getJson("/dashboard/projects/{$project->id}/flags/999999");
    $foreign = $this->actingAs($owner)->getJson("/dashboard/projects/{$otherProject->id}/flags/{$otherFlag->id}");
    $foreign->assertStatus($missing->getStatusCode())->assertExactJson($missing->json());
});

it('does not disclose or mutate another owners flag through management commands', function (): void {
    $owner = User::factory()->create();
    $otherProject = projectWithEnvironments(User::factory()->create());
    $otherFlag = FeatureFlag::factory()->for($otherProject)->create(['name' => 'Private flag']);
    $environment = $otherProject->environments()->firstOrFail();
    EnvironmentFlag::factory()->for($otherFlag)->for($environment)->create();

    $missingUpdate = $this->actingAs($owner)->patchJson('/dashboard/projects/999999/flags/999999', [
        'name' => 'Changed',
        'description' => null,
    ]);
    $foreignUpdate = $this->actingAs($owner)->patchJson(
        "/dashboard/projects/{$otherProject->id}/flags/{$otherFlag->id}",
        ['name' => 'Changed', 'description' => null],
    );
    $missingArchive = $this->actingAs($owner)->postJson('/dashboard/projects/999999/flags/999999/archive');
    $foreignArchive = $this->actingAs($owner)
        ->postJson("/dashboard/projects/{$otherProject->id}/flags/{$otherFlag->id}/archive");

    $foreignUpdate->assertStatus($missingUpdate->getStatusCode())->assertExactJson($missingUpdate->json());
    $foreignArchive->assertStatus($missingArchive->getStatusCode())->assertExactJson($missingArchive->json());
    expect($otherFlag->refresh()->name)->toBe('Private flag')
        ->and($otherFlag->statusValue())->toBe(FeatureFlagStatus::Active)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('updates only mutable metadata and records changes without changing key or state', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $flag = FeatureFlag::factory()->for($project)->create(['key' => 'stable-key']);
    foreach ($project->environments as $environment) {
        EnvironmentFlag::factory()->for($flag)->for($environment)->create(['enabled' => $environment->key === 'development']);
    }

    $this->actingAs($owner)->patchJson("/dashboard/projects/{$project->id}/flags/{$flag->id}", [
        'name' => 'Renamed',
        'description' => 'Updated',
        'key' => 'changed-key',
        'status' => 'archived',
        'environment_states' => [['enabled' => false]],
    ])->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.key', 'stable-key')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.environment_states.0.enabled', true);

    expect(AuditEvent::query()->where('action', 'feature_flag.updated')->count())->toBe(1);
});

it('changes one environment idempotently and rejects cross project combinations', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $flag = FeatureFlag::factory()->for($project)->create();
    foreach ($project->environments as $environment) {
        EnvironmentFlag::factory()->for($flag)->for($environment)->create();
    }
    $development = $project->environments->firstWhere('key', 'development');
    $production = $project->environments->firstWhere('key', 'production');

    $path = "/dashboard/projects/{$project->id}/flags/{$flag->id}/environments/{$development->id}";
    $this->actingAs($owner)->putJson($path, ['enabled' => true])->assertOk()
        ->assertJsonPath('data.environment_states.0.enabled', true)
        ->assertJsonPath('data.environment_states.2.enabled', false);
    $this->actingAs($owner)->putJson($path, ['enabled' => true])->assertOk();
    expect(AuditEvent::query()->where('action', 'feature_flag.enabled')->count())->toBe(1)
        ->and(EnvironmentFlag::query()->where('environment_id', $production->id)->value('enabled'))->toBeFalse();

    $otherProject = projectWithEnvironments($owner);
    $otherEnvironment = $otherProject->environments()->firstOrFail();
    $this->actingAs($owner)->putJson(
        "/dashboard/projects/{$project->id}/flags/{$flag->id}/environments/{$otherEnvironment->id}",
        ['enabled' => false],
    )->assertNotFound();
});

it('rolls back a state change when its audit event fails', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $flag = FeatureFlag::factory()->for($project)->create();
    $environment = $project->environments()->firstOrFail();
    $state = EnvironmentFlag::factory()->for($flag)->for($environment)->create();
    $dispatcher = AuditEvent::getEventDispatcher();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));
    try {
        $this->actingAs($owner)->putJson(
            "/dashboard/projects/{$project->id}/flags/{$flag->id}/environments/{$environment->id}",
            ['enabled' => true],
        )->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }
    expect($state->refresh()->enabled)->toBeFalse();
});

it('archives idempotently, retains states, and prevents later mutations', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $flag = FeatureFlag::factory()->for($project)->create();
    $environment = $project->environments()->firstOrFail();
    $state = EnvironmentFlag::factory()->for($flag)->for($environment)->create();
    $path = "/dashboard/projects/{$project->id}/flags/{$flag->id}/archive";

    $this->actingAs($owner)->postJson($path)->assertOk()->assertJsonPath('data.status', 'archived');
    $this->actingAs($owner)->postJson($path)->assertOk();
    expect($flag->refresh()->statusValue())->toBe(FeatureFlagStatus::Archived)
        ->and($state->fresh())->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'feature_flag.archived')->count())->toBe(1);

    $this->actingAs($owner)->patchJson("/dashboard/projects/{$project->id}/flags/{$flag->id}", [
        'name' => 'Cannot update',
        'description' => null,
    ])->assertForbidden();
    $this->actingAs($owner)->putJson(
        "/dashboard/projects/{$project->id}/flags/{$flag->id}/environments/{$environment->id}",
        ['enabled' => true],
    )->assertForbidden();
});

it('rolls back archival when the audit event cannot be stored', function (): void {
    $owner = User::factory()->create();
    $project = projectWithEnvironments($owner);
    $flag = FeatureFlag::factory()->for($project)->create();
    $dispatcher = AuditEvent::getEventDispatcher();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));
    try {
        $this->actingAs($owner)
            ->postJson("/dashboard/projects/{$project->id}/flags/{$flag->id}/archive")
            ->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }

    expect($flag->refresh()->statusValue())->toBe(FeatureFlagStatus::Active)
        ->and(AuditEvent::query()->where('action', 'feature_flag.archived')->count())->toBe(0);
});

it('enforces feature flag and environment state uniqueness in the database', function (): void {
    $project = projectWithEnvironments(User::factory()->create());
    $flag = FeatureFlag::factory()->for($project)->create(['key' => 'checkout']);
    $environment = $project->environments()->firstOrFail();
    EnvironmentFlag::factory()->for($flag)->for($environment)->create();

    expect(fn () => FeatureFlag::factory()->for($project)->create(['key' => 'checkout']))
        ->toThrow(QueryException::class);
    expect(fn () => EnvironmentFlag::factory()->for($flag)->for($environment)->create())
        ->toThrow(QueryException::class);
});

it('rejects cross project environment and flag state records at the database boundary', function (): void {
    $firstProject = projectWithEnvironments(User::factory()->create());
    $secondProject = projectWithEnvironments(User::factory()->create());
    $flag = FeatureFlag::factory()->for($firstProject)->create();
    $otherEnvironment = $secondProject->environments()->firstOrFail();

    expect(fn () => EnvironmentFlag::factory()->for($flag)->for($otherEnvironment)->create())
        ->toThrow(QueryException::class);
});
