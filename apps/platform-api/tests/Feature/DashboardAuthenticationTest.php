<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear(hash('sha256', 'owner@example.com').'|127.0.0.1');
    RateLimiter::clear(hash('sha256', 'missing@example.com').'|127.0.0.1');
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

it('checks an unknown owner against a precomputed hash without generating one per request', function (): void {
    Hash::spy();

    $this->postJson('/dashboard/auth/session', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized();

    Hash::shouldHaveReceived('check')->once();
    Hash::shouldNotHaveReceived('make');
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

it('rate limits repeated invalid login attempts and clears attempts after success', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/dashboard/auth/session', [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertTooManyRequests()->assertExactJson([
        'message' => 'Too many sign-in attempts. Please try again later.',
    ]);

    RateLimiter::clear(hash('sha256', 'owner@example.com').'|127.0.0.1');

    $this->postJson('/dashboard/auth/session', [
        'email' => 'owner@example.com',
        'password' => 'correct-password',
    ])->assertOk();

    expect(RateLimiter::attempts(hash('sha256', 'owner@example.com').'|127.0.0.1'))->toBe(0);
});

it('persists only a password hash for an owner', function (): void {
    $user = User::factory()->create(['password' => 'correct-password']);

    expect($user->getRawOriginal('password'))
        ->not->toBe('correct-password')
        ->and(Hash::check('correct-password', $user->getRawOriginal('password')))->toBeTrue();
});
