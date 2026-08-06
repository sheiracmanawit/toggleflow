<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\AuditEventAction;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\EnvironmentFlag;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates the complete demo release fixture idempotently only when demo mode is enabled', function (): void {
    config()->set('toggleflow.demo', [
        'enabled' => true,
        'name' => 'Demo Owner',
        'email' => 'owner@toggleflow.test',
        'password' => 'toggleflow-demo',
    ]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->sole();

    expect($user->name)->toBe('Demo Owner')
        ->and($user->email)->toBe('owner@toggleflow.test')
        ->and($user->getRawOriginal('password'))->not->toBe('toggleflow-demo')
        ->and(Hash::check('toggleflow-demo', $user->getRawOriginal('password')))->toBeTrue()
        ->and(Project::query()->count())->toBe(1)
        ->and(FeatureFlag::query()->count())->toBe(1)
        ->and(EnvironmentFlag::query()->count())->toBe(3)
        ->and(AuditEvent::query()->count())->toBe(2);

    $project = Project::query()->with('environments')->sole();

    expect($project->name)->toBe('Checkout Service')
        ->and($project->slug)->toBe('checkout-service')
        ->and($project->status)->toBe(ProjectStatus::Active)
        ->and($project->environments->pluck('key')->all())
        ->toBe(['development', 'staging', 'production']);

    $flag = FeatureFlag::query()->with('environmentStates')->sole();

    expect($flag->name)->toBe('New checkout')
        ->and($flag->key)->toBe('new-checkout')
        ->and($flag->status)->toBe(FeatureFlagStatus::Active)
        ->and($flag->environmentStates)->toHaveCount(3)
        ->and($flag->environmentStates->every(
            fn (EnvironmentFlag $state): bool => $state->enabled === false,
        ))->toBeTrue()
        ->and(AuditEvent::query()->pluck('action')->all())
        ->toBe([AuditEventAction::ProjectCreated, AuditEventAction::FeatureFlagCreated]);
});

it('does not create a demo owner when demo mode is disabled', function (): void {
    config()->set('toggleflow.demo.enabled', false);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0)
        ->and(Project::query()->count())->toBe(0)
        ->and(FeatureFlag::query()->count())->toBe(0);
});
