<?php

declare(strict_types=1);

it('allows credentialed requests only from the configured dashboard origin', function (): void {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->getJson('/dashboard/auth/demo')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

it('does not allow an unconfigured dashboard origin', function (): void {
    $this->withHeader('Origin', 'https://untrusted.example')
        ->getJson('/dashboard/auth/demo')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

it('keeps dashboard and evaluation route namespaces separate', function (): void {
    $this->getJson('/dashboard/auth/session')->assertUnauthorized();
    $this->getJson('/api/v1/auth/session')->assertNotFound();
    $this->getJson('/api/v1/missing')->assertNotFound()->assertExactJson([
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'The requested API resource was not found.',
        ],
    ]);
    $this->getJson('/api/management/foundation')->assertNotFound();
});

it('does not couple the independently built dashboard to Laravel HTML routes', function (): void {
    $this->get('/')->assertNotFound();
});
