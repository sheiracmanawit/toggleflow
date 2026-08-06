<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
