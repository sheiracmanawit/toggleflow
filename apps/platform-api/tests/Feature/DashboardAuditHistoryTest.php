<?php

declare(strict_types=1);

use App\Enums\AuditEventAction;
use App\Models\ApiKey;
use App\Models\AuditEvent;
use App\Models\Environment;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns deterministic paginated owner-scoped audit history with safe historical context', function (): void {
    $owner = User::factory()->create(['name' => 'Release Owner']);
    $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Checkout']);
    $flag = FeatureFlag::factory()->for($project)->create(['name' => 'New checkout']);

    foreach (range(1, 21) as $number) {
        AuditEvent::query()->create([
            'project_id' => $project->id,
            'actor_id' => $owner->id,
            'action' => AuditEventAction::FeatureFlagUpdated,
            'subject_type' => $flag->getMorphClass(),
            'subject_id' => $flag->id,
            'metadata' => [
                'project' => ['name' => 'Checkout'],
                'subject' => ['name' => "New checkout {$number}"],
                'actor' => ['name' => 'Release Owner'],
                'before' => ['name' => 'Old name'],
                'after' => ['name' => "New checkout {$number}"],
                'secret_hash' => 'must-not-serialize',
            ],
            'created_at' => now(),
        ]);
    }

    $response = $this->actingAs($owner)->getJson("/dashboard/projects/{$project->id}/audit-events");

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.total', 21)
        ->assertJsonPath('data.0.id', AuditEvent::query()->max('id'))
        ->assertJsonPath('data.0.actor.name', 'Release Owner')
        ->assertJsonPath('data.0.project.name', 'Checkout')
        ->assertJsonMissing(['secret_hash' => 'must-not-serialize']);

    $this->actingAs($owner)
        ->getJson("/dashboard/projects/{$project->id}/audit-events?page=2")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', AuditEvent::query()->min('id'));
});

it('rejects unauthenticated, missing, and foreign project history without disclosure', function (): void {
    $owner = User::factory()->create();
    $foreignProject = Project::factory()->create();

    $this->getJson("/dashboard/projects/{$foreignProject->id}/audit-events")->assertUnauthorized();

    $missing = $this->actingAs($owner)->getJson('/dashboard/projects/999999/audit-events');
    $foreign = $this->actingAs($owner)->getJson("/dashboard/projects/{$foreignProject->id}/audit-events");

    $missing->assertNotFound();
    $foreign->assertStatus($missing->getStatusCode())->assertExactJson($missing->json());
});

it('keeps archived subject labels readable without loading the live subject', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    AuditEvent::query()->create([
        'project_id' => $project->id,
        'actor_id' => null,
        'action' => AuditEventAction::FeatureFlagArchived,
        'subject_type' => FeatureFlag::class,
        'subject_id' => 999999,
        'metadata' => [
            'project' => ['name' => $project->name],
            'subject' => ['name' => 'Retired checkout'],
            'actor' => ['name' => 'Former owner'],
        ],
    ]);

    $this->actingAs($owner)
        ->getJson("/dashboard/projects/{$project->id}/audit-events")
        ->assertOk()
        ->assertJsonPath('data.0.subject.name', 'Retired checkout')
        ->assertJsonPath('data.0.actor.name', 'Former owner');
});

it('keeps audit rows written with legacy metadata readable', function (): void {
    $owner = User::factory()->create(['name' => 'Current owner']);
    $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Current project name']);
    $flag = FeatureFlag::factory()->for($project)->create(['name' => 'Current flag name']);
    $production = Environment::factory()->for($project)->create([
        'name' => 'Production',
        'key' => 'production',
    ]);

    AuditEvent::query()->create([
        'project_id' => $project->id,
        'actor_id' => $owner->id,
        'action' => AuditEventAction::ProjectCreated,
        'subject_type' => Project::class,
        'subject_id' => $project->id,
        'metadata' => ['after' => ['name' => 'Original project name']],
        'created_at' => now()->subSeconds(2),
    ]);
    AuditEvent::query()->create([
        'project_id' => $project->id,
        'actor_id' => $owner->id,
        'action' => AuditEventAction::FeatureFlagArchived,
        'subject_type' => FeatureFlag::class,
        'subject_id' => $flag->id,
        'metadata' => [
            'before' => ['status' => 'active'],
            'after' => ['status' => 'archived'],
        ],
        'created_at' => now()->subSecond(),
    ]);
    AuditEvent::query()->create([
        'project_id' => $project->id,
        'actor_id' => $owner->id,
        'action' => AuditEventAction::ApiKeyRevoked,
        'subject_type' => ApiKey::class,
        'subject_id' => 999999,
        'metadata' => [
            'name' => 'Legacy production key',
            'prefix' => 'tf_live_legacy',
            'environment_id' => $production->id,
        ],
        'created_at' => now(),
    ]);

    $this->actingAs($owner)
        ->getJson("/dashboard/projects/{$project->id}/audit-events")
        ->assertOk()
        ->assertJsonPath('data.0.subject.name', 'Legacy production key')
        ->assertJsonPath('data.0.environment.id', $production->id)
        ->assertJsonPath('data.0.environment.key', 'production')
        ->assertJsonPath('data.0.environment.name', 'Production')
        ->assertJsonPath('data.1.subject.name', 'Current flag name')
        ->assertJsonPath('data.2.subject.name', 'Original project name')
        ->assertJsonPath('data.2.project.name', 'Current project name');
});
