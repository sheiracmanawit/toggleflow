<?php

declare(strict_types=1);

it('does not couple the independently built dashboard to Laravel', function (): void {
    $this->get('/')->assertNotFound();
});

it('allows credentialed requests only from configured dashboard origins', function (): void {
    $this->withHeader('Origin', 'http://localhost:5173')
        ->getJson('/api/management/foundation')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
});

it('does not allow an unconfigured dashboard origin', function (): void {
    $this->withHeader('Origin', 'https://untrusted.example')
        ->getJson('/api/management/foundation')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

it('keeps management and evaluation foundation routes separate', function (): void {
    $this->getJson('/api/management/foundation')
        ->assertOk()
        ->assertExactJson(['boundary' => 'management']);

    $this->getJson('/api/v1/foundation')
        ->assertOk()
        ->assertExactJson(['boundary' => 'evaluation', 'version' => 'v1']);
});

it('does not let the SPA swallow unknown API paths', function (): void {
    $this->getJson('/api/v1/missing')->assertNotFound();
});
