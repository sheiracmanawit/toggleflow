<?php

declare(strict_types=1);

use App\Modules\Evaluation\RateLimiting\EvaluationRateLimit;
use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Credentials\Contracts\AuthenticatesEnvironmentKeys;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use App\Modules\ReleaseManagement\Models\ApiKey;
use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\Environment;
use App\Modules\ReleaseManagement\Models\EnvironmentFlag;
use App\Modules\ReleaseManagement\Models\FeatureFlag;
use App\Modules\ReleaseManagement\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use RuntimeException as UnexpectedEvaluationFailure;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * @return array{project: Project, development: Environment, production: Environment}
 */
function evaluationProject(): array
{
    $project = Project::factory()->for(User::factory(), 'owner')->create();
    $development = $project->environments()->create([
        'name' => 'Development',
        'key' => 'development',
        'color' => '#2563eb',
        'position' => 1,
    ]);
    $production = $project->environments()->create([
        'name' => 'Production',
        'key' => 'production',
        'color' => '#7c3aed',
        'position' => 2,
    ]);

    return compact('project', 'development', 'production');
}

/**
 * @return array{apiKey: ApiKey, credential: string}
 */
function evaluationCredential(Environment $environment, bool $revoked = false): array
{
    $prefix = bin2hex(random_bytes(8));
    $secret = bin2hex(random_bytes(32));
    $factory = ApiKey::factory()->for($environment);
    if ($revoked) {
        $factory = $factory->revoked();
    }

    $apiKey = $factory->create([
        'prefix' => $prefix,
        'secret_hash' => Hash::make($secret),
    ]);

    return [
        'apiKey' => $apiKey,
        'credential' => "tf_env_{$prefix}_{$secret}",
    ];
}

/** @return array{Authorization: string, Accept: string} */
function evaluationHeaders(string $credential): array
{
    return [
        'Authorization' => "Bearer {$credential}",
        'Accept' => 'application/json',
    ];
}

function assertInvalidEvaluationCredential(TestCase $test, string $credential): void
{
    $test->withHeaders(evaluationHeaders($credential))
        ->getJson('/api/v1/flags/new-checkout')
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'INVALID_API_KEY',
                'message' => 'The supplied API key is invalid or has been revoked.',
            ],
        ]);
}

beforeEach(function (): void {
    EvaluationRateLimit::clearForInvalidIp('127.0.0.1');
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    EvaluationRateLimit::clearForInvalidIp('127.0.0.1');
});

it('returns the configured boolean and reflects the next persisted state without auditing evaluation', function (): void {
    ['project' => $project, 'development' => $environment] = evaluationProject();
    $flag = FeatureFlag::factory()->for($project)->create(['key' => 'new-checkout']);
    $state = EnvironmentFlag::factory()->for($environment)->for($flag)->create(['enabled' => false]);
    ['apiKey' => $apiKey, 'credential' => $credential] = evaluationCredential($environment);

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson('/api/v1/flags/new-checkout')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'key' => 'new-checkout',
                'value' => false,
                'reason' => 'STATIC',
            ],
        ]);

    $state->forceFill(['enabled' => true])->save();

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson('/api/v1/flags/new-checkout')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'key' => 'new-checkout',
                'value' => true,
                'reason' => 'STATIC',
            ],
        ]);

    expect(AuditEvent::query()->count())->toBe(0);
    EvaluationRateLimit::clearForApiKey($apiKey);
});

it('derives project and environment only from the credential', function (): void {
    [
        'project' => $project,
        'development' => $development,
        'production' => $production,
    ] = evaluationProject();
    $flag = FeatureFlag::factory()->for($project)->create(['key' => 'new-checkout']);
    EnvironmentFlag::factory()->for($development)->for($flag)->create(['enabled' => false]);
    EnvironmentFlag::factory()->for($production)->for($flag)->create(['enabled' => true]);
    $otherProject = evaluationProject()['project'];
    $otherFlag = FeatureFlag::factory()->for($otherProject)->create(['key' => 'new-checkout']);
    EnvironmentFlag::factory()
        ->for($otherProject->environments()->firstOrFail())
        ->for($otherFlag)
        ->create(['enabled' => true]);
    ['apiKey' => $apiKey, 'credential' => $credential] = evaluationCredential($development);

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson(
            "/api/v1/flags/new-checkout?project_id={$otherProject->id}&environment_id={$production->id}",
        )
        ->assertOk()
        ->assertJsonPath('data.value', false)
        ->assertJsonPath('data.reason', 'STATIC');

    EvaluationRateLimit::clearForApiKey($apiKey);
});

it('returns safe false results that distinguish missing archived and unconfigured flags', function (): void {
    ['project' => $project, 'development' => $environment] = evaluationProject();
    FeatureFlag::factory()->for($project)->create([
        'key' => 'archived-flag',
        'status' => FeatureFlagStatus::Archived,
    ]);
    FeatureFlag::factory()->for($project)->create(['key' => 'unconfigured-flag']);
    ['apiKey' => $apiKey, 'credential' => $credential] = evaluationCredential($environment);
    $headers = evaluationHeaders($credential);

    $this->withHeaders($headers)->getJson('/api/v1/flags/missing-flag')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'key' => 'missing-flag',
                'value' => false,
                'reason' => 'FLAG_NOT_FOUND',
            ],
        ]);
    $this->withHeaders($headers)->getJson('/api/v1/flags/archived-flag')
        ->assertOk()
        ->assertJsonPath('data.value', false)
        ->assertJsonPath('data.reason', 'FLAG_ARCHIVED');
    $this->withHeaders($headers)->getJson('/api/v1/flags/unconfigured-flag')
        ->assertOk()
        ->assertJsonPath('data.value', false)
        ->assertJsonPath('data.reason', 'CONFIGURATION_MISSING');

    expect(AuditEvent::query()->count())->toBe(0);
    EvaluationRateLimit::clearForApiKey($apiKey);
});

it('distinguishes a missing bearer token without exposing internal state', function (): void {
    $this->getJson('/api/v1/flags/new-checkout')
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'MISSING_API_KEY',
                'message' => 'An environment API key is required.',
            ],
        ]);
});

it('returns one invalid response for malformed unknown mismatched revoked and inactive project keys', function (): void {
    ['project' => $project, 'development' => $environment] = evaluationProject();
    ['credential' => $credential] = evaluationCredential($environment);
    [, , $prefix] = explode('_', $credential, 4);
    ['credential' => $revokedCredential] = evaluationCredential($environment, revoked: true);

    ['project' => $archivedProject, 'development' => $archivedEnvironment] = evaluationProject();
    $archivedProject->forceFill(['status' => ProjectStatus::Archived])->save();
    ['credential' => $archivedCredential] = evaluationCredential($archivedEnvironment);

    $invalidCredentials = [
        'not-an-environment-key',
        'tf_env_'.str_repeat('f', 16).'_'.str_repeat('e', 64),
        "tf_env_{$prefix}_".str_repeat('0', 64),
        $revokedCredential,
        $archivedCredential,
    ];

    foreach ($invalidCredentials as $invalidCredential) {
        assertInvalidEvaluationCredential($this, $invalidCredential);
    }

    expect($project->statusValue())->toBe(ProjectStatus::Active);
});

it('updates last used after valid authentication without moving it backwards', function (): void {
    ['development' => $environment] = evaluationProject();
    ['apiKey' => $apiKey, 'credential' => $credential] = evaluationCredential($environment);
    $headers = evaluationHeaders($credential);
    $firstUse = CarbonImmutable::parse('2026-07-29 10:00:00 UTC');
    CarbonImmutable::setTestNow($firstUse);

    $this->withHeaders($headers)->getJson('/api/v1/flags/missing')->assertOk();
    expect($apiKey->refresh()->last_used_at?->equalTo($firstUse))->toBeTrue();

    $futureUse = $firstUse->addHour();
    $apiKey->forceFill(['last_used_at' => $futureUse])->save();
    CarbonImmutable::setTestNow($firstUse->addMinute());
    $this->withHeaders($headers)->getJson('/api/v1/flags/missing')->assertOk();
    expect($apiKey->refresh()->last_used_at?->equalTo($futureUse))->toBeTrue();

    $latestUse = $futureUse->addMinute();
    CarbonImmutable::setTestNow($latestUse);
    $this->withHeaders($headers)->getJson('/api/v1/flags/missing')->assertOk();
    expect($apiKey->refresh()->last_used_at?->equalTo($latestUse))->toBeTrue();

    EvaluationRateLimit::clearForApiKey($apiKey);
});

it('does not grant dashboard management access to an evaluation credential', function (): void {
    ['project' => $project, 'development' => $environment] = evaluationProject();
    ['credential' => $credential] = evaluationCredential($environment);

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson("/dashboard/projects/{$project->id}")
        ->assertUnauthorized();
});

it('rate limits successful evaluations by key id with the documented response and headers', function (): void {
    ['development' => $environment] = evaluationProject();
    ['apiKey' => $apiKey, 'credential' => $credential] = evaluationCredential($environment);
    ['apiKey' => $secondApiKey, 'credential' => $secondCredential] = evaluationCredential($environment);
    EvaluationRateLimit::clearForApiKey($apiKey);
    EvaluationRateLimit::clearForApiKey($secondApiKey);

    foreach (range(1, EvaluationRateLimit::MAX_ATTEMPTS - 1) as $attempt) {
        RateLimiter::hit(EvaluationRateLimit::storageKeyForApiKey($apiKey), 60);
    }

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson('/api/v1/flags/missing')
        ->assertOk();
    expect(EvaluationRateLimit::attemptsForApiKey($apiKey))
        ->toBe(EvaluationRateLimit::MAX_ATTEMPTS)
        ->and(EvaluationRateLimit::attemptsForInvalidIp('127.0.0.1'))
        ->toBe(0);

    $this->withHeaders(evaluationHeaders($credential))
        ->getJson('/api/v1/flags/missing')
        ->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', (string) EvaluationRateLimit::MAX_ATTEMPTS)
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('Retry-After')
        ->assertExactJson([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many evaluation requests. Please try again later.',
            ],
        ]);

    $this->withHeaders(evaluationHeaders($secondCredential))
        ->getJson('/api/v1/flags/missing')
        ->assertOk();
    expect(EvaluationRateLimit::attemptsForApiKey($secondApiKey))->toBe(1);

    EvaluationRateLimit::clearForApiKey($apiKey);
    EvaluationRateLimit::clearForApiKey($secondApiKey);
});

it('rate limits invalid evaluations by normalized ip so fake prefixes cannot bypass it', function (): void {
    foreach (range(1, EvaluationRateLimit::MAX_ATTEMPTS - 1) as $attempt) {
        RateLimiter::hit(EvaluationRateLimit::storageKeyForInvalidIp('127.0.0.1'), 60);
    }

    assertInvalidEvaluationCredential(
        $this,
        'tf_env_'.str_repeat('a', 16).'_'.str_repeat('b', 64),
    );
    expect(EvaluationRateLimit::attemptsForInvalidIp('127.0.0.1'))
        ->toBe(EvaluationRateLimit::MAX_ATTEMPTS);

    $this->withHeaders(evaluationHeaders(
        'tf_env_'.str_repeat('c', 16).'_'.str_repeat('d', 64),
    ))->getJson('/api/v1/flags/new-checkout')
        ->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', (string) EvaluationRateLimit::MAX_ATTEMPTS)
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('Retry-After')
        ->assertExactJson([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many evaluation requests. Please try again later.',
            ],
        ]);
});

it('counts missing key responses through the invalid ip threshold before returning rate limited', function (): void {
    foreach (range(1, EvaluationRateLimit::MAX_ATTEMPTS - 1) as $attempt) {
        RateLimiter::hit(EvaluationRateLimit::storageKeyForInvalidIp('127.0.0.1'), 60);
    }

    $this->getJson('/api/v1/flags/new-checkout')
        ->assertUnauthorized()
        ->assertExactJson([
            'error' => [
                'code' => 'MISSING_API_KEY',
                'message' => 'An environment API key is required.',
            ],
        ]);
    expect(EvaluationRateLimit::attemptsForInvalidIp('127.0.0.1'))
        ->toBe(EvaluationRateLimit::MAX_ATTEMPTS);

    $this->getJson('/api/v1/flags/new-checkout')
        ->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', (string) EvaluationRateLimit::MAX_ATTEMPTS)
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('Retry-After')
        ->assertExactJson([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many evaluation requests. Please try again later.',
            ],
        ]);
});

it('rejects a limited invalid ip before credential hash verification', function (): void {
    $authenticator = new class implements AuthenticatesEnvironmentKeys
    {
        public int $attempts = 0;

        public function authenticate(string $credential): ?ApiKey
        {
            $this->attempts++;

            return null;
        }
    };
    app()->instance(AuthenticatesEnvironmentKeys::class, $authenticator);

    foreach (range(1, EvaluationRateLimit::MAX_ATTEMPTS) as $attempt) {
        RateLimiter::hit(EvaluationRateLimit::storageKeyForInvalidIp('127.0.0.1'), 60);
    }

    $this->withHeaders(evaluationHeaders(
        'tf_env_'.str_repeat('e', 16).'_'.str_repeat('f', 64),
    ))->getJson('/api/v1/flags/new-checkout')
        ->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', (string) EvaluationRateLimit::MAX_ATTEMPTS)
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('Retry-After')
        ->assertExactJson([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many evaluation requests. Please try again later.',
            ],
        ]);

    expect($authenticator->attempts)->toBe(0);
});

it('sanitizes unexpected evaluation failures', function (): void {
    Route::get(
        '/api/v1/test-only-unexpected-evaluation-error',
        static fn (): never => throw new UnexpectedEvaluationFailure('secret database detail'),
    );

    $this->getJson('/api/v1/test-only-unexpected-evaluation-error')
        ->assertServerError()
        ->assertExactJson([
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An unexpected error occurred.',
            ],
        ])
        ->assertDontSee('secret database detail');
});
