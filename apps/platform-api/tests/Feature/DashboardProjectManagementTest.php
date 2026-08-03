<?php

declare(strict_types=1);

use App\Actions\Projects\CreateProject;
use App\Enums\AuditEventAction;
use App\Enums\ProjectStatus;
use App\Models\AuditEvent;
use App\Models\Environment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException as AuditStorageFailure;

uses(RefreshDatabase::class);

it('requires an authenticated owner for every project operation', function (): void {
    $project = Project::factory()->create();

    $this->getJson('/dashboard/projects')->assertUnauthorized();
    $this->postJson('/dashboard/projects', [])->assertUnauthorized();
    $this->getJson("/dashboard/projects/{$project->id}")->assertUnauthorized();
    $this->patchJson("/dashboard/projects/{$project->id}", [])->assertUnauthorized();
    $this->postJson("/dashboard/projects/{$project->id}/archive")->assertUnauthorized();
});

it('creates a project, its fixed environments, and its audit event atomically', function (): void {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson('/dashboard/projects', [
        'name' => ' Checkout Service ',
        'slug' => ' Checkout Service ',
        'description' => ' Controls checkout releases. ',
        'owner_id' => User::factory()->create()->id,
        'status' => 'archived',
        'environments' => [['key' => 'custom']],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Checkout Service')
        ->assertJsonPath('data.slug', 'checkout-service')
        ->assertJsonPath('data.description', 'Controls checkout releases.')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.environments.0.key', 'development')
        ->assertJsonPath('data.environments.1.key', 'staging')
        ->assertJsonPath('data.environments.2.key', 'production')
        ->assertJsonMissingPath('data.owner_id');

    $project = Project::query()->sole();

    expect($project->owner_id)->toBe($owner->id)
        ->and($project->environments()->orderBy('position')->pluck('key')->all())
        ->toBe(['development', 'staging', 'production']);

    $this->assertDatabaseHas('audit_events', [
        'project_id' => $project->id,
        'actor_id' => $owner->id,
        'action' => AuditEventAction::ProjectCreated->value,
    ]);

    $event = $project->auditEvents()->sole();
    expect($event->action)->toBe(AuditEventAction::ProjectCreated)
        ->and($event->subject)->toBeInstanceOf(Project::class);
});

it('rolls back project creation when its audit event cannot be stored', function (): void {
    $owner = User::factory()->create();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));

    try {
        $this->actingAs($owner)
            ->postJson('/dashboard/projects', ['name' => 'Checkout', 'slug' => 'checkout'])
            ->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }

    expect(Project::query()->count())->toBe(0)
        ->and(Environment::query()->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('validates project input and scopes slug uniqueness to the owner', function (): void {
    $firstOwner = User::factory()->create();
    $secondOwner = User::factory()->create();
    Project::factory()->for($firstOwner, 'owner')->create(['slug' => 'checkout']);

    $this->actingAs($firstOwner)
        ->postJson('/dashboard/projects', [
            'name' => '',
            'slug' => 'checkout',
            'description' => str_repeat('x', 1001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'slug', 'description']);

    $this->actingAs($secondOwner)
        ->postJson('/dashboard/projects', ['name' => 'Checkout', 'slug' => 'checkout'])
        ->assertCreated();
});

it('translates an owner slug uniqueness race into a validation failure', function (): void {
    $owner = User::factory()->create();
    Project::factory()->for($owner, 'owner')->create(['slug' => 'checkout']);

    expect(fn () => app(CreateProject::class)->execute($owner, [
        'name' => 'Another Checkout',
        'slug' => 'checkout',
        'description' => null,
    ]))->toThrow(ValidationException::class);

    expect(Project::query()->where('owner_id', $owner->id)->count())->toBe(1)
        ->and(Environment::query()->count())->toBe(0)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('lists only active projects owned by the authenticated owner', function (): void {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $older = Project::factory()->for($owner, 'owner')->create(['name' => 'Older']);
    $newer = Project::factory()->for($owner, 'owner')->create(['name' => 'Newer']);
    Project::factory()->for($owner, 'owner')->archived()->create(['name' => 'Archived']);
    Project::factory()->for($otherOwner, 'owner')->create(['name' => 'Other owner']);
    $older->forceFill(['updated_at' => now()->subDay()])->save();
    $newer->touch();

    $this->actingAs($owner)
        ->getJson('/dashboard/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Newer')
        ->assertJsonPath('data.1.name', 'Older')
        ->assertJsonMissing(['name' => 'Archived'])
        ->assertJsonMissing(['name' => 'Other owner']);
});

it('returns the same not found response for missing and other owner projects', function (): void {
    $owner = User::factory()->create();
    $otherProject = Project::factory()->create();

    $missing = $this->actingAs($owner)->getJson('/dashboard/projects/999999');
    $unauthorized = $this->actingAs($owner)->getJson("/dashboard/projects/{$otherProject->id}");

    $missing->assertNotFound();
    $unauthorized->assertStatus($missing->getStatusCode())
        ->assertExactJson($missing->json());
});

it('does not disclose or mutate another owners project through update or archive commands', function (): void {
    $owner = User::factory()->create();
    $otherProject = Project::factory()->create(['name' => 'Other owner project']);

    $missingUpdate = $this->actingAs($owner)->patchJson('/dashboard/projects/999999', [
        'name' => 'Changed',
        'description' => null,
    ]);
    $unauthorizedUpdate = $this->actingAs($owner)->patchJson("/dashboard/projects/{$otherProject->id}", [
        'name' => 'Changed',
        'description' => null,
    ]);

    $missingArchive = $this->actingAs($owner)->postJson('/dashboard/projects/999999/archive');
    $unauthorizedArchive = $this->actingAs($owner)
        ->postJson("/dashboard/projects/{$otherProject->id}/archive");

    $unauthorizedUpdate->assertStatus($missingUpdate->getStatusCode())
        ->assertExactJson($missingUpdate->json());
    $unauthorizedArchive->assertStatus($missingArchive->getStatusCode())
        ->assertExactJson($missingArchive->json());

    expect($otherProject->refresh()->name)->toBe('Other owner project')
        ->and($otherProject->statusValue())->toBe(ProjectStatus::Active)
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('shows fixed environments in their stable order and updates only mutable metadata', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create([
        'slug' => 'stable-slug',
    ]);
    $project->environments()->createMany([
        ['name' => 'Production', 'key' => 'production', 'color' => '#7c3aed', 'position' => 3],
        ['name' => 'Development', 'key' => 'development', 'color' => '#2563eb', 'position' => 1],
        ['name' => 'Staging', 'key' => 'staging', 'color' => '#b45309', 'position' => 2],
    ]);

    $this->actingAs($owner)
        ->getJson("/dashboard/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.environments.0.key', 'development')
        ->assertJsonPath('data.environments.1.key', 'staging')
        ->assertJsonPath('data.environments.2.key', 'production');

    $this->actingAs($owner)
        ->patchJson("/dashboard/projects/{$project->id}", [
            'name' => 'Renamed Project',
            'description' => 'Updated description',
            'slug' => 'changed-slug',
            'status' => 'archived',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed Project')
        ->assertJsonPath('data.slug', 'stable-slug')
        ->assertJsonPath('data.status', 'active');

    expect(AuditEvent::query()->where('action', AuditEventAction::ProjectUpdated)->count())->toBe(1);
});

it('does not audit a project no-op and rolls back an update when audit storage fails', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create([
        'name' => 'Checkout',
        'description' => 'Original description',
    ]);

    $this->actingAs($owner)->patchJson("/dashboard/projects/{$project->id}", [
        'name' => 'Checkout',
        'description' => 'Original description',
    ])->assertOk();
    expect(AuditEvent::query()->count())->toBe(0);

    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));

    try {
        $this->actingAs($owner)->patchJson("/dashboard/projects/{$project->id}", [
            'name' => 'Uncommitted rename',
            'description' => 'Original description',
        ])->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }

    expect($project->refresh()->name)->toBe('Checkout')
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('archives idempotently, retains children, and records one transactional audit event', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $environment = Environment::factory()->for($project)->create();

    $this->actingAs($owner)
        ->postJson("/dashboard/projects/{$project->id}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $this->actingAs($owner)
        ->postJson("/dashboard/projects/{$project->id}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    expect($project->refresh()->statusValue())->toBe(ProjectStatus::Archived)
        ->and($environment->fresh())->not->toBeNull()
        ->and(AuditEvent::query()->where('action', AuditEventAction::ProjectArchived->value)->count())->toBe(1);

    $this->actingAs($owner)
        ->patchJson("/dashboard/projects/{$project->id}", [
            'name' => 'Cannot change',
            'description' => null,
        ])
        ->assertForbidden();
});

it('rolls back archival when the audit event cannot be stored', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));

    try {
        $this->actingAs($owner)
            ->postJson("/dashboard/projects/{$project->id}/archive")
            ->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }

    expect($project->refresh()->statusValue())->toBe(ProjectStatus::Active)
        ->and(AuditEvent::query()->where('action', AuditEventAction::ProjectArchived->value)->count())->toBe(0);
});

it('enforces environment keys and positions within each project at the database boundary', function (): void {
    $project = Project::factory()->create();
    Environment::factory()->for($project)->create(['key' => 'development', 'position' => 1]);

    expect(fn () => Environment::factory()->for($project)->create([
        'key' => 'development',
        'position' => 2,
    ]))->toThrow(QueryException::class);

    expect(fn () => Environment::factory()->for($project)->create([
        'key' => 'staging',
        'position' => 1,
    ]))->toThrow(QueryException::class);
});
