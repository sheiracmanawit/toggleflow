<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\Identity\RateLimiting\LoginRateLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    LoginRateLimit::clearFor('owner@example.com', '127.0.0.1');
    LoginRateLimit::clearFor('missing@example.com', '127.0.0.1');
});

it('signs in an owner and exposes only the minimal session owner', function (): void {
    $user = User::factory()->create([
        'name' => 'Project Owner',
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    $this->postJson('/dashboard/auth/session', [
        'email' => ' OWNER@example.com ',
        'password' => 'correct-password',
    ])->assertOk()->assertExactJson([
        'data' => [
            'id' => $user->id,
            'name' => 'Project Owner',
            'email' => 'owner@example.com',
        ],
    ]);

    $this->getJson('/dashboard/auth/session')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Project Owner',
                'email' => 'owner@example.com',
            ],
        ])
        ->assertJsonMissing(['password' => true]);
});

it('regenerates the session identifier after successful login', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);
    $this->withSession(['pre_authentication_marker' => true]);
    $beforeLogin = $this->app['session']->driver()->getId();

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    expect($this->app['session']->driver()->getId())->not->toBe($beforeLogin);
});

it('returns the same generic response for an unknown owner and a wrong password', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    $wrongPassword = $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'wrong-password',
    ]);
    $unknownOwner = $this->postJson('/dashboard/auth/session', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ]);

    $wrongPassword->assertUnauthorized()->assertExactJson([
        'message' => 'The provided credentials are invalid.',
    ]);
    $unknownOwner->assertStatus($wrongPassword->getStatusCode())
        ->assertExactJson($wrongPassword->json());
});

it('validates login structure without exposing account state', function (): void {
    $this->postJson('/dashboard/auth/session', [
        'email' => 'not-an-email',
        'password' => '',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('requires a session to inspect or destroy dashboard authentication', function (): void {
    $this->getJson('/dashboard/auth/session')->assertUnauthorized();
    $this->deleteJson('/dashboard/auth/session')->assertUnauthorized();
});

it('invalidates the session on logout and denies later dashboard access', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    $this->deleteJson('/dashboard/auth/session')->assertNoContent();
    $this->app['auth']->forgetGuards();
    $this->getJson('/dashboard/auth/session')->assertUnauthorized();
});

it('uses route middleware to count only failed login attempts', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    $this->postJson('/dashboard/auth/session', [
        'email' => 'not-an-email',
        'password' => '',
    ])->assertUnprocessable();

    expect(LoginRateLimit::attemptsFor('not-an-email', '127.0.0.1'))->toBe(0);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/dashboard/auth/session', [
            'email' => ' OWNER@example.com ',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertTooManyRequests()
        ->assertHeader('X-RateLimit-Limit', '5')
        ->assertHeader('Retry-After')
        ->assertExactJson([
            'message' => 'Too many sign-in attempts. Please try again later.',
        ]);
});

it('clears accumulated failed login attempts after success', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    foreach (range(1, 4) as $attempt) {
        $this->postJson('/dashboard/auth/session', [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    expect(LoginRateLimit::attemptsFor('owner@example.com', '127.0.0.1'))->toBe(4);

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    expect(LoginRateLimit::attemptsFor('owner@example.com', '127.0.0.1'))->toBe(0);
});

it('persists only a password hash for an owner', function (): void {
    $user = User::factory()->create(['password' => 'correct-password']);

    expect($user->getRawOriginal('password'))
        ->not->toBe('correct-password')
        ->and(Hash::check('correct-password', $user->getRawOriginal('password')))->toBeTrue();
});
