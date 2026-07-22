<?php

declare(strict_types=1);

$environment = env('APP_ENV', 'production');

return [
    'demo' => [
        'enabled' => in_array($environment, ['local', 'demo'], true)
            && (bool) env('TOGGLEFLOW_DEMO_ENABLED', false),
        'name' => env('TOGGLEFLOW_DEMO_NAME', 'Demo Owner'),
        'email' => env('TOGGLEFLOW_DEMO_EMAIL', 'owner@toggleflow.test'),
        'password' => env('TOGGLEFLOW_DEMO_PASSWORD', 'toggleflow-demo'),
    ],
];
