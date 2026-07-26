<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureFlagAuditAction: string
{
    case Created = 'feature_flag.created';
    case Updated = 'feature_flag.updated';
    case Enabled = 'feature_flag.enabled';
    case Disabled = 'feature_flag.disabled';
    case Archived = 'feature_flag.archived';
}
