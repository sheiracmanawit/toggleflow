<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\Environment;
use App\Modules\ReleaseManagement\Models\EnvironmentFlag;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('requires an authenticated owner for the dashboard summary', function (): void {
    $this->getJson('/dashboard/summary')->assertUnauthorized();
});

it('returns owner scoped release counts, project summaries, and safe recent activity', function (): void {
    $owner = User::factory()->create(['name' => 'Release Owner']);
    $otherOwner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Checkout']);
    $production = Environment::factory()->for($project)->create([
        'name' => 'Production',
        'key' => 'production',
        'position' => 3,
    ]);
    $activeFlag = FeatureFlag::factory()->for($project)->create(['name' => 'New checkout']);
    EnvironmentFlag::factory()->for($production)->for($activeFlag)->create(['enabled' => true]);
    FeatureFlag::factory()->for($project)->archived()->create();

    $archivedProject = Project::factory()->for($owner, 'owner')->archived()->create();
    FeatureFlag::factory()->for($archivedProject)->create();

    $otherProject = Project::factory()->for($otherOwner, 'owner')->create();
    $otherProduction = Environment::factory()->for($otherProject)->create([
        'name' => 'Production',
        'key' => 'production',
        'position' => 3,
    ]);
    $otherFlag = FeatureFlag::factory()->for($otherProject)->create();
    EnvironmentFlag::factory()->for($otherProduction)->for($otherFlag)->create(['enabled' => true]);

    AuditEvent::query()->create([
        'project_id' => $project->id,
        'actor_id' => null,
        'action' => AuditEventAction::FeatureFlagEnabled,
        'subject_type' => $activeFlag->getMorphClass(),
        'subject_id' => $activeFlag->id,
        'metadata' => [
            'project' => ['name' => 'Historical checkout'],
            'subject' => ['name' => 'Historical new checkout'],
            'actor' => ['name' => 'Former release owner'],
            'environment' => ['id' => $production->id, 'key' => 'production', 'name' => 'Production'],
            'secret' => 'must-not-be-serialized',
        ],
    ]);
    AuditEvent::query()->create([
        'project_id' => $otherProject->id,
        'actor_id' => $otherOwner->id,
        'action' => AuditEventAction::FeatureFlagEnabled,
        'subject_type' => $otherFlag->getMorphClass(),
        'subject_id' => $otherFlag->id,
        'metadata' => [],
    ]);

    $this->actingAs($owner)
        ->getJson('/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.project_count', 1)
        ->assertJsonPath('data.active_flag_count', 1)
        ->assertJsonPath('data.production_enabled_count', 1)
        ->assertJsonCount(1, 'data.projects')
        ->assertJsonPath('data.projects.0.name', 'Checkout')
        ->assertJsonPath('data.projects.0.active_flag_count', 1)
        ->assertJsonCount(1, 'data.recent_activity')
        ->assertJsonPath('data.recent_activity.0.action', 'feature_flag.enabled')
        ->assertJsonPath('data.recent_activity.0.project.name', 'Historical checkout')
        ->assertJsonPath('data.recent_activity.0.subject.name', 'Historical new checkout')
        ->assertJsonPath('data.recent_activity.0.actor.name', 'Former release owner')
        ->assertJsonPath('data.recent_activity.0.environment.name', 'Production')
        ->assertJsonMissingPath('data.recent_activity.0.metadata')
        ->assertJsonMissing(['secret' => 'must-not-be-serialized'])
        ->assertJsonMissing(['name' => $otherProject->name]);
});

it('distinguishes a genuinely empty dashboard from unavailable or missing production state', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    FeatureFlag::factory()->for($project)->create();

    $this->actingAs($owner)
        ->getJson('/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.project_count', 1)
        ->assertJsonPath('data.active_flag_count', 1)
        ->assertJsonPath('data.production_enabled_count', 0)
        ->assertJsonPath('data.projects.0.production_enabled_count', 0)
        ->assertJsonPath('data.recent_activity', []);

    $emptyOwner = User::factory()->create();
    $this->actingAs($emptyOwner)
        ->getJson('/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.project_count', 0)
        ->assertJsonPath('data.active_flag_count', 0)
        ->assertJsonPath('data.production_enabled_count', 0)
        ->assertJsonPath('data.projects', [])
        ->assertJsonPath('data.recent_activity', []);
});

it('bounds recent activity and orders events newest first', function (): void {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    foreach (range(1, 10) as $index) {
        AuditEvent::query()->create([
            'project_id' => $project->id,
            'actor_id' => $owner->id,
            'action' => AuditEventAction::ProjectCreated,
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
            'metadata' => [],
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $response = $this->actingAs($owner)->getJson('/dashboard/summary')->assertOk();

    $response->assertJsonCount(8, 'data.recent_activity');
    expect($response->json('data.recent_activity.0.id'))->toBe(AuditEvent::query()->max('id'))
        ->and($response->json('data.recent_activity.7.id'))->toBe(AuditEvent::query()->orderByDesc('id')->skip(7)->value('id'));
});

it('keeps summary queries bounded as project and flag volume grows', function (): void {
    $owner = User::factory()->create();

    foreach (range(1, 4) as $projectIndex) {
        $project = Project::factory()->for($owner, 'owner')->create();
        $production = Environment::factory()->for($project)->create([
            'name' => 'Production',
            'key' => 'production',
            'position' => 3,
        ]);

        foreach (range(1, 4) as $flagIndex) {
            $flag = FeatureFlag::factory()->for($project)->create();
            EnvironmentFlag::factory()->for($production)->for($flag)->create(['enabled' => true]);

            AuditEvent::query()->create([
                'project_id' => $project->id,
                'actor_id' => $owner->id,
                'action' => AuditEventAction::FeatureFlagEnabled,
                'subject_type' => $flag->getMorphClass(),
                'subject_id' => $flag->id,
                'metadata' => [],
                'created_at' => now()->addSeconds(($projectIndex * 10) + $flagIndex),
            ]);
        }
    }

    $queryCount = 0;
    DB::listen(static function () use (&$queryCount): void {
        $queryCount++;
    });

    $this->actingAs($owner)
        ->getJson('/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.project_count', 4)
        ->assertJsonPath('data.active_flag_count', 16)
        ->assertJsonPath('data.production_enabled_count', 16)
        ->assertJsonCount(4, 'data.projects')
        ->assertJsonCount(8, 'data.recent_activity');

    expect($queryCount)->toBeLessThanOrEqual(9);
});
