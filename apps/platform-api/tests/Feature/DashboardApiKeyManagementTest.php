<?php

declare(strict_types=1);

use App\Actions\ApiKeys\IssueEnvironmentKey;
use App\Actions\ApiKeys\RevokeEnvironmentKey;
use App\Actions\Projects\ArchiveProject;
use App\Enums\AuditEventAction;
use App\Enums\ProjectStatus;
use App\Models\ApiKey;
use App\Models\AuditEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException as AuditStorageFailure;

uses(RefreshDatabase::class);

function apiKeyProject(User $owner): Project
{
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->environments()->createMany([
        ['name' => 'Development', 'key' => 'development', 'color' => '#2563eb', 'position' => 1],
        ['name' => 'Staging', 'key' => 'staging', 'color' => '#b45309', 'position' => 2],
        ['name' => 'Production', 'key' => 'production', 'color' => '#7c3aed', 'position' => 3],
    ]);

    return $project;
}

it('requires authentication for credential management', function (): void {
    $project = apiKeyProject(User::factory()->create());
    $environment = $project->environments()->firstOrFail();
    $apiKey = ApiKey::factory()->for($environment)->create();

    $this->getJson("/dashboard/projects/{$project->id}/api-keys")->assertUnauthorized();
    $this->postJson("/dashboard/projects/{$project->id}/environments/{$environment->id}/api-keys", [
        'name' => 'Production app',
    ])->assertUnauthorized();
    $this->postJson("/dashboard/projects/{$project->id}/api-keys/{$apiKey->id}/revoke")
        ->assertUnauthorized();
});

it('issues a credential once without persisting or auditing its secret', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $environment = $project->environments->firstWhere('key', 'production');

    $response = $this->actingAs($owner)->postJson(
        "/dashboard/projects/{$project->id}/environments/{$environment->id}/api-keys",
        ['name' => ' Checkout production '],
    )->assertCreated()
        ->assertJsonPath('data.name', 'Checkout production')
        ->assertJsonPath('data.environment.key', 'production')
        ->assertJsonPath('data.state', 'active')
        ->assertJsonMissingPath('data.secret_hash');

    $credential = $response->json('credential');
    expect($credential)->toBeString()->toStartWith('tf_env_');
    [, , $prefix, $secret] = explode('_', $credential, 4);

    $apiKey = ApiKey::query()->sole();
    expect($apiKey->prefix)->toBe($prefix)
        ->and(Hash::check($secret, $apiKey->secret_hash))->toBeTrue()
        ->and(json_encode($apiKey->getAttributes()))->not->toContain($credential)
        ->and(json_encode($apiKey->getAttributes()))->not->toContain($secret);

    $event = AuditEvent::query()->sole();
    expect($event->action)->toBe(AuditEventAction::ApiKeyCreated)
        ->and($event->subject)->toBeInstanceOf(ApiKey::class)
        ->and($apiKey->auditEvents()->sole()->is($event))->toBeTrue()
        ->and($event->metadata)->toMatchArray([
            'name' => 'Checkout production',
            'prefix' => $prefix,
            'environment_id' => $environment->id,
        ])
        ->and(json_encode($event->metadata))->not->toContain($credential)
        ->and(json_encode($event->metadata))->not->toContain($secret)
        ->and(json_encode($event->metadata))->not->toContain($apiKey->secret_hash);
});

it('lists only safe metadata for credentials in an owned project', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $environment = $project->environments()->firstOrFail();
    $apiKey = ApiKey::factory()->for($environment)->create(['name' => 'Server']);

    $this->actingAs($owner)->getJson("/dashboard/projects/{$project->id}/api-keys")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Server')
        ->assertJsonPath('data.0.prefix', $apiKey->prefix)
        ->assertJsonPath('data.0.environment.id', $environment->id)
        ->assertJsonMissingPath('credential')
        ->assertJsonMissingPath('data.0.secret_hash');
});

it('rejects invalid names and cross project environment combinations safely', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $otherProject = apiKeyProject($owner);
    $otherEnvironment = $otherProject->environments()->firstOrFail();

    $this->actingAs($owner)->postJson(
        "/dashboard/projects/{$project->id}/environments/{$project->environments()->firstOrFail()->id}/api-keys",
        ['name' => str_repeat('x', 121)],
    )->assertUnprocessable()->assertJsonValidationErrors(['name']);

    $this->actingAs($owner)->postJson(
        "/dashboard/projects/{$project->id}/environments/{$otherEnvironment->id}/api-keys",
        ['name' => 'Hidden'],
    )->assertNotFound();

    expect(ApiKey::query()->count())->toBe(0);
});

it('does not disclose or mutate credentials across owners', function (): void {
    $owner = User::factory()->create();
    $otherProject = apiKeyProject(User::factory()->create());
    $otherEnvironment = $otherProject->environments()->firstOrFail();
    $otherKey = ApiKey::factory()->for($otherEnvironment)->create();

    $missingList = $this->actingAs($owner)->getJson('/dashboard/projects/999999/api-keys');
    $foreignList = $this->actingAs($owner)->getJson("/dashboard/projects/{$otherProject->id}/api-keys");
    $foreignList->assertStatus($missingList->getStatusCode())->assertExactJson($missingList->json());

    $missingRevoke = $this->actingAs($owner)->postJson('/dashboard/projects/999999/api-keys/999999/revoke');
    $foreignRevoke = $this->actingAs($owner)
        ->postJson("/dashboard/projects/{$otherProject->id}/api-keys/{$otherKey->id}/revoke");
    $foreignRevoke->assertStatus($missingRevoke->getStatusCode())->assertExactJson($missingRevoke->json());

    expect($otherKey->refresh()->isRevoked())->toBeFalse()
        ->and(AuditEvent::query()->count())->toBe(0);
});

it('supports overlapping active credentials and revokes one idempotently', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $environment = $project->environments()->firstOrFail();
    $first = ApiKey::factory()->for($environment)->create(['name' => 'First']);
    $second = ApiKey::factory()->for($environment)->create(['name' => 'Second']);
    $path = "/dashboard/projects/{$project->id}/api-keys/{$first->id}/revoke";

    $this->actingAs($owner)->postJson($path)->assertOk()
        ->assertJsonPath('data.state', 'revoked')
        ->assertJsonMissingPath('credential');
    $this->actingAs($owner)->postJson($path)->assertOk();

    expect($first->refresh()->isRevoked())->toBeTrue()
        ->and($second->refresh()->isRevoked())->toBeFalse()
        ->and(AuditEvent::query()->where('action', AuditEventAction::ApiKeyRevoked->value)->count())
        ->toBe(1);
});

it('prevents issuance and revocation for an archived project', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $environment = $project->environments()->firstOrFail();
    $apiKey = ApiKey::factory()->for($environment)->create();
    $project->forceFill(['status' => ProjectStatus::Archived])->save();

    $this->actingAs($owner)->postJson(
        "/dashboard/projects/{$project->id}/environments/{$environment->id}/api-keys",
        ['name' => 'No longer allowed'],
    )->assertForbidden();
    $this->actingAs($owner)->postJson(
        "/dashboard/projects/{$project->id}/api-keys/{$apiKey->id}/revoke",
    )->assertForbidden();
});

it('rejects authorized credential commands when project archival commits before their transactions start', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $authorizedEnvironment = $project->environments()->firstOrFail();
    $authorizedKey = ApiKey::factory()->for($authorizedEnvironment)->create();

    app(ArchiveProject::class)->execute($project, $owner);

    expect(
        fn () => app(IssueEnvironmentKey::class)
            ->execute($authorizedEnvironment, $owner, 'No longer allowed'),
    )->toThrow(AuthorizationException::class);
    expect(
        fn (): ApiKey => app(RevokeEnvironmentKey::class)->execute($authorizedKey, $owner),
    )->toThrow(AuthorizationException::class);
    expect(ApiKey::query()->count())->toBe(1)
        ->and($authorizedKey->refresh()->isRevoked())->toBeFalse()
        ->and(AuditEvent::query()->pluck('action')->all())->toBe([
            AuditEventAction::ProjectArchived,
        ]);
});

it('rolls back issuance and revocation when audit storage fails', function (): void {
    $owner = User::factory()->create();
    $project = apiKeyProject($owner);
    $environment = $project->environments()->firstOrFail();
    $dispatcher = AuditEvent::getEventDispatcher();
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));

    try {
        $this->actingAs($owner)->postJson(
            "/dashboard/projects/{$project->id}/environments/{$environment->id}/api-keys",
            ['name' => 'Rollback'],
        )->assertServerError();
    } finally {
        $dispatcher?->forget($eventName);
    }
    expect(ApiKey::query()->count())->toBe(0);

    $apiKey = ApiKey::factory()->for($environment)->create();
    $dispatcher?->listen($eventName, fn (): never => throw new AuditStorageFailure('audit unavailable'));
    try {
        expect(
            fn (): ApiKey => app(RevokeEnvironmentKey::class)->execute($apiKey, $owner),
        )->toThrow(AuditStorageFailure::class);
    } finally {
        $dispatcher?->forget($eventName);
    }
    expect($apiKey->refresh()->isRevoked())->toBeFalse();
});
