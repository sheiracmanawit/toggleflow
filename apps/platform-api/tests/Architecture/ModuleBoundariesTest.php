<?php

declare(strict_types=1);
use Illuminate\Support\ServiceProvider;

arch('core remains product agnostic')
    ->expect('App\Core')
    ->not->toUse([
        'App\Modules\Identity',
        'App\Modules\ReleaseManagement',
        'App\Modules\Evaluation',
    ]);

arch('identity does not depend on product modules')
    ->expect('App\Modules\Identity')
    ->not->toUse([
        'App\Modules\ReleaseManagement',
        'App\Modules\Evaluation',
    ]);

arch('release management does not depend on evaluation')
    ->expect('App\Modules\ReleaseManagement')
    ->not->toUse('App\Modules\Evaluation');

arch('evaluation does not depend on identity workflows')
    ->expect('App\Modules\Evaluation')
    ->not->toUse([
        'App\Modules\Identity\Http',
        'App\Modules\Identity\RateLimiting',
    ]);

arch('each product module has one service provider')
    ->expect([
        'App\Modules\Identity\IdentityServiceProvider',
        'App\Modules\ReleaseManagement\ReleaseManagementServiceProvider',
        'App\Modules\Evaluation\EvaluationServiceProvider',
    ])
    ->toExtend(ServiceProvider::class);

it('removes the legacy layer-first application directories', function (): void {
    $applicationDirectory = dirname(__DIR__, 2).'/app';

    foreach (['Actions', 'Contracts', 'Data', 'Domain', 'Enums', 'Http', 'Models', 'Policies', 'Providers'] as $directory) {
        expect(is_dir($applicationDirectory.'/'.$directory))->toBeFalse($directory.' must not remain at app root.');
    }
});

it('keeps route ownership inside modules', function (): void {
    $baseDirectory = dirname(__DIR__, 2);

    expect($baseDirectory.'/routes/dashboard.php')->not->toBeFile()
        ->and($baseDirectory.'/routes/api.php')->not->toBeFile()
        ->and($baseDirectory.'/app/Modules/Identity/routes.php')->toBeFile()
        ->and($baseDirectory.'/app/Modules/ReleaseManagement/routes.php')->toBeFile()
        ->and($baseDirectory.'/app/Modules/Evaluation/routes.php')->toBeFile();
});
