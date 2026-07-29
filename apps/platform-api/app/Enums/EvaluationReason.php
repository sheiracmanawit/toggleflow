<?php

declare(strict_types=1);

namespace App\Enums;

enum EvaluationReason: string
{
    case Static = 'STATIC';
    case FlagNotFound = 'FLAG_NOT_FOUND';
    case FlagArchived = 'FLAG_ARCHIVED';
    case ConfigurationMissing = 'CONFIGURATION_MISSING';
}
