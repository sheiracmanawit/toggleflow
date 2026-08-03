<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEventAction: string
{
    case ProjectCreated = 'project.created';
    case ProjectUpdated = 'project.updated';
    case ProjectArchived = 'project.archived';
    case FeatureFlagCreated = 'feature_flag.created';
    case FeatureFlagUpdated = 'feature_flag.updated';
    case FeatureFlagEnabled = 'feature_flag.enabled';
    case FeatureFlagDisabled = 'feature_flag.disabled';
    case FeatureFlagArchived = 'feature_flag.archived';
    case ApiKeyCreated = 'api_key.created';
    case ApiKeyRevoked = 'api_key.revoked';
}
