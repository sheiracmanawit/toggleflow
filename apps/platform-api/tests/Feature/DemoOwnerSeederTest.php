<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates the demo owner idempotently only when demo mode is enabled', function (): void {
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
        ->and(Hash::check('toggleflow-demo', $user->getRawOriginal('password')))->toBeTrue();
});

it('does not create or expose demo credentials when demo mode is disabled', function (): void {
    config()->set('toggleflow.demo.enabled', false);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(0);
    $this->getJson('/dashboard/auth/demo')->assertOk()->assertExactJson(['data' => null]);
});

it('exposes configured credentials only when demo mode is enabled', function (): void {
    config()->set('toggleflow.demo', [
        'enabled' => true,
        'name' => 'Demo Owner',
        'email' => 'owner@toggleflow.test',
        'password' => 'toggleflow-demo',
    ]);

    $this->getJson('/dashboard/auth/demo')->assertOk()->assertExactJson([
        'data' => [
            'email' => 'owner@toggleflow.test',
            'password' => 'toggleflow-demo',
        ],
    ]);
});
