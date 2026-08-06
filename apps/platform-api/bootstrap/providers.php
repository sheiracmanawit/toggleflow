<?php

use App\Core\Providers\CoreServiceProvider;
use App\Modules\Evaluation\EvaluationServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\ReleaseManagement\ReleaseManagementServiceProvider;

return [
    CoreServiceProvider::class,
    IdentityServiceProvider::class,
    ReleaseManagementServiceProvider::class,
    EvaluationServiceProvider::class,
];
