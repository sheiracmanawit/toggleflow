<?php

declare(strict_types=1);

it('keeps the required Laravel foundation dependencies installed', function (): void {
    $composerPath = dirname(__DIR__, 4).'/apps/platform-api/composer.json';
    $composer = json_decode((string) file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require'])->toHaveKeys(['laravel/framework', 'laravel/sanctum']);
});

it('does not commit generated credentials in the environment example', function (): void {
    $environmentPath = dirname(__DIR__, 4).'/apps/platform-api/.env.example';
    $environment = (string) file_get_contents($environmentPath);

    expect($environment)->not->toMatch('/^(APP_KEY|DB_PASSWORD|AWS_ACCESS_KEY_ID|AWS_SECRET_ACCESS_KEY)=.+$/m');
});
