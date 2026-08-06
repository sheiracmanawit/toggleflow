<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

it('allows credentialed requests only from the configured dashboard origin', function (): void {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->getJson('/dashboard/auth/session')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

it('does not allow an unconfigured dashboard origin', function (): void {
    $this->withHeader('Origin', 'https://untrusted.example')
        ->getJson('/dashboard/auth/session')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

it('keeps dashboard and evaluation route namespaces separate', function (): void {
    $this->getJson('/dashboard/auth/session')->assertUnauthorized();
    $this->getJson('/dashboard/auth/demo')->assertNotFound()->assertExactJson([
        'message' => 'The requested dashboard resource was not found.',
    ]);
    $this->getJson('/api/v1/auth/session')->assertNotFound();
    $this->getJson('/api/v1/missing')->assertNotFound()->assertExactJson([
        'error' => [
            'code' => 'ENDPOINT_NOT_FOUND',
            'message' => 'The requested API endpoint was not found.',
        ],
    ]);
    $this->getJson('/api/management/foundation')->assertNotFound();
});

it('does not couple the independently built dashboard to Laravel HTML routes', function (): void {
    $this->get('/')->assertNotFound();
});

it('preserves the management route contract while routes are owned by modules', function (): void {
    $routes = [
        'dashboard.auth.session.store' => ['POST', 'dashboard/auth/session', false],
        'dashboard.auth.session.show' => ['GET', 'dashboard/auth/session', true],
        'dashboard.auth.session.destroy' => ['DELETE', 'dashboard/auth/session', true],
        'dashboard.summary' => ['GET', 'dashboard/summary', true],
        'dashboard.projects.index' => ['GET', 'dashboard/projects', true],
        'dashboard.projects.store' => ['POST', 'dashboard/projects', true],
        'dashboard.projects.show' => ['GET', 'dashboard/projects/{project}', true],
        'dashboard.projects.update' => ['PATCH', 'dashboard/projects/{project}', true],
        'dashboard.projects.archive' => ['POST', 'dashboard/projects/{project}/archive', true],
        'dashboard.projects.audit-events.index' => ['GET', 'dashboard/projects/{project}/audit-events', true],
        'dashboard.projects.flags.index' => ['GET', 'dashboard/projects/{project}/flags', true],
        'dashboard.projects.flags.store' => ['POST', 'dashboard/projects/{project}/flags', true],
        'dashboard.projects.flags.show' => ['GET', 'dashboard/projects/{project}/flags/{flag}', true],
        'dashboard.projects.flags.update' => ['PATCH', 'dashboard/projects/{project}/flags/{flag}', true],
        'dashboard.projects.flags.archive' => ['POST', 'dashboard/projects/{project}/flags/{flag}/archive', true],
        'dashboard.projects.flags.state' => ['PUT', 'dashboard/projects/{project}/flags/{flag}/environments/{environment}', true],
        'dashboard.projects.api-keys.index' => ['GET', 'dashboard/projects/{project}/api-keys', true],
        'dashboard.projects.api-keys.store' => ['POST', 'dashboard/projects/{project}/environments/{environment}/api-keys', true],
        'dashboard.projects.api-keys.revoke' => ['POST', 'dashboard/projects/{project}/api-keys/{apiKey}/revoke', true],
    ];

    foreach ($routes as $name => [$method, $uri, $requiresAuthentication]) {
        $route = RouteFacade::getRoutes()->getByName($name);

        expect($route)->toBeInstanceOf(Route::class)
            ->and($route?->uri())->toBe($uri)
            ->and($route?->methods())->toContain($method)
            ->and($route?->gatherMiddleware())->toContain('web');

        if ($requiresAuthentication) {
            expect($route?->gatherMiddleware())->toContain('auth:sanctum');
        } else {
            expect($route?->gatherMiddleware())->not->toContain('auth:sanctum');
        }
    }
});
