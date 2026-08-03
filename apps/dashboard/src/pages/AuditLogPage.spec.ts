import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { auditEventService, projectService } from '../services';
import type { AuditEvent, Project } from '../types';
import AuditLogPage from './AuditLogPage.vue';

const project = { id: 1, name: 'Checkout', environments: [] } as unknown as Project;
const event: AuditEvent = {
    id: 8,
    action: 'feature_flag.enabled',
    project: { id: 1, name: 'Checkout' },
    subject: { type: 'FeatureFlag', id: 3, name: 'New checkout' },
    actor: { id: 2, name: 'Release Owner' },
    environment: { id: 4, key: 'production', name: 'Production' },
    changes: { before: { enabled: false }, after: { enabled: true } },
    created_at: '2026-08-03T08:00:00.000Z',
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects/:projectId', component: { template: '<div />' } },
            { path: '/projects/:projectId/audit-log', component: AuditLogPage },
        ],
    });
    await router.push('/projects/1/audit-log');
    await router.isReady();
    const wrapper = mount(AuditLogPage, { global: { plugins: [router] } });
    await flushPromises();
    return wrapper;
};

describe('AuditLogPage', () => {
    afterEach(() => vi.restoreAllMocks());

    it('renders human-readable, environment-aware history and pagination', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(auditEventService, 'list').mockResolvedValue({
            events: [event],
            currentPage: 1,
            lastPage: 2,
            total: 21,
        });
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Enabled the feature flag');
        expect(wrapper.text()).toContain('Release Owner');
        expect(wrapper.text()).toContain('New checkout');
        expect(wrapper.text()).toContain('Production');
        expect(wrapper.get('time').attributes('datetime')).toBe(event.created_at);
        expect(wrapper.get('nav[aria-label="Audit history pages"]').text()).toContain('Page 1 of 2');
    });

    it('distinguishes an empty response from a retryable initial failure', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        const list = vi
            .spyOn(auditEventService, 'list')
            .mockResolvedValue({ events: [], currentPage: 1, lastPage: 1, total: 0 });
        const wrapper = await mountPage();
        expect(wrapper.text()).toContain('No management changes yet');

        list.mockRejectedValueOnce(new Error('offline'));
        await (wrapper.vm as unknown as { load: (page: number) => Promise<void> }).load(1);
        await flushPromises();
        expect(wrapper.text()).toContain('Audit history unavailable');
        expect(wrapper.text()).not.toContain('No management changes yet');
        expect(wrapper.get('button').text()).toBe('Try again');
    });

    it('keeps confirmed history visible when pagination fails', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        const list = vi.spyOn(auditEventService, 'list').mockResolvedValue({
            events: [event],
            currentPage: 1,
            lastPage: 2,
            total: 21,
        });
        const wrapper = await mountPage();

        list.mockRejectedValueOnce(new Error('offline'));
        await (wrapper.vm as unknown as { load: (page: number) => Promise<void> }).load(2);
        await flushPromises();

        expect(wrapper.text()).toContain('Audit history could not be refreshed');
        expect(wrapper.text()).toContain('Enabled the feature flag');
        expect(wrapper.text()).toContain('Page 1 of 2');
        expect(wrapper.get('button').text()).toBe('Try again');
    });
});
