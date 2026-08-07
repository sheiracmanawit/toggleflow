import { describe, expect, it } from 'vitest';

import { auditEventDescription } from './auditEvents';

const baseEvent = {
    project: { name: 'Checkout' },
    subject: { name: 'New checkout' },
    actor: { name: 'Demo Owner' },
    environment: null,
};

describe('auditEventDescription', () => {
    it.each([
        ['project.created', 'created project'],
        ['project.updated', 'updated project'],
        ['project.archived', 'archived project'],
        ['feature_flag.created', 'created feature flag'],
        ['feature_flag.updated', 'updated feature flag'],
        ['feature_flag.archived', 'archived feature flag'],
        ['feature_flag.enabled', 'enabled feature flag'],
        ['feature_flag.disabled', 'disabled feature flag'],
        ['api_key.created', 'issued API key'],
        ['api_key.revoked', 'revoked API key'],
    ])('identifies the resource for %s', (action, description) => {
        expect(auditEventDescription({ ...baseEvent, action })).toContain(description);
    });

    it('includes environment and project context for environment-scoped actions', () => {
        expect(
            auditEventDescription({
                ...baseEvent,
                action: 'api_key.created',
                subject: { name: 'Server key' },
                environment: { key: 'production', name: 'Production' },
            }),
        ).toBe('Demo Owner issued API key Server key for Production in project Checkout');
    });

    it('uses a clear fallback for an unknown action', () => {
        expect(auditEventDescription({ ...baseEvent, action: 'future.action' })).toBe(
            'Demo Owner changed release configuration for New checkout in project Checkout',
        );
    });
});
