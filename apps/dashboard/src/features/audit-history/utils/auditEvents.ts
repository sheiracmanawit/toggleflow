interface AuditDisplayEvent {
    action: string;
    project: { name: string };
    subject: { name: string };
    actor: { name: string } | null;
    environment: { key: string | null; name: string | null } | null;
}

const actionDescriptions: Record<string, string> = {
    'project.created': 'created project',
    'project.updated': 'updated project',
    'project.archived': 'archived project',
    'feature_flag.created': 'created feature flag',
    'feature_flag.updated': 'updated feature flag',
    'feature_flag.archived': 'archived feature flag',
    'feature_flag.enabled': 'enabled feature flag',
    'feature_flag.disabled': 'disabled feature flag',
    'api_key.created': 'issued API key',
    'api_key.revoked': 'revoked API key',
};

export const auditEventDescription = (event: AuditDisplayEvent): string => {
    const actor = event.actor?.name ?? 'System';
    const action = actionDescriptions[event.action];

    if (!action) {
        return `${actor} changed release configuration for ${event.subject.name} in project ${event.project.name}`;
    }

    if (event.action.startsWith('project.')) {
        return `${actor} ${action} ${event.subject.name}`;
    }

    const environment = event.environment?.name ?? event.environment?.key;
    const environmentContext = environment ? ` for ${environment}` : '';

    return `${actor} ${action} ${event.subject.name}${environmentContext} in project ${event.project.name}`;
};
