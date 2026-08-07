import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { dashboardService } from '@features/dashboard';
import type { DashboardSummary } from '@features/dashboard';
import DashboardPage from './DashboardPage.vue';

const summary: DashboardSummary = {
    project_count: 1,
    active_flag_count: 2,
    production_enabled_count: 1,
    projects: [
        {
            id: 1,
            name: 'Checkout',
            slug: 'checkout',
            active_flag_count: 2,
            production_enabled_count: 1,
            updated_at: '2026-07-31T00:00:00.000Z',
        },
    ],
    recent_activity: [
        {
            id: 10,
            action: 'feature_flag.enabled',
            project: { id: 1, name: 'Checkout' },
            subject: { type: 'FeatureFlag', id: 5, name: 'New checkout' },
            actor: { id: 1, name: 'Demo Owner' },
            environment: { id: 3, key: 'production', name: 'Production' },
            changes: { before: { enabled: false }, after: { enabled: true } },
            created_at: '2026-07-31T00:00:00.000Z',
        },
    ],
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/app', component: DashboardPage },
            { path: '/projects', component: { template: '<h1>Projects</h1>' } },
            { path: '/projects/:projectId', component: { template: '<h1>Project</h1>' } },
            { path: '/projects/:projectId/audit-log', component: { template: '<h1>Audit history</h1>' } },
        ],
    });
    await router.push('/app');
    await router.isReady();
    const wrapper = mount(DashboardPage, { global: { plugins: [router] } });
    await flushPromises();
    return wrapper;
};

describe('DashboardPage', () => {
    afterEach(() => vi.restoreAllMocks());

    it('stops loading skeleton animation when reduced motion is requested', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockReturnValue(new Promise<DashboardSummary>(() => undefined));
        const wrapper = await mountPage();

        const skeletons = wrapper.get('[role="status"]').findAll('.motion-reduce\\:animate-none');
        expect(skeletons).toHaveLength(5);
        skeletons.forEach((skeleton) => {
            expect(skeleton.classes()).toContain('motion-reduce:animate-none');
        });
    });

    it('shows authoritative counts, project summaries, and recent activity', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockResolvedValue(summary);
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Active projects');
        expect(wrapper.text()).toContain('Active flags');
        expect(wrapper.text()).toContain('Enabled in Production');
        expect(wrapper.text()).toContain('Checkout');
        expect(wrapper.text()).toContain(
            'Demo Owner enabled feature flag New checkout for Production in project Checkout',
        );
        expect(wrapper.text()).toContain('Production enabled');
        expect(wrapper.text()).toContain('View release state');
        expect(wrapper.text()).toContain('Recent changes');

        const metricLists = wrapper.findAll('[aria-label="Portfolio release state"] dl');
        expect(metricLists).toHaveLength(3);
        metricLists.forEach((metric) => {
            expect(metric.findAll(':scope > dt')).toHaveLength(1);
            expect(metric.findAll(':scope > dd')).toHaveLength(1);
        });

        const links = wrapper.findAll('a');
        expect(links.some((link) => link.attributes('href') === '/projects/1')).toBe(true);
        expect(links.some((link) => link.attributes('href') === '/projects/1/audit-log')).toBe(true);
        expect(wrapper.findAll('.text-environment-production').length).toBeGreaterThan(0);
    });

    it('links unknown activity safely to project audit history', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockResolvedValue({
            ...summary,
            recent_activity: [{ ...summary.recent_activity[0], action: 'future.action', environment: null }],
        });
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('changed release configuration');
        const activityLink = wrapper.get('a[aria-label*="View Checkout audit history"]');
        expect(activityLink.attributes('href')).toBe('/projects/1/audit-log');
    });

    it('shows a next action for a genuinely empty account', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockResolvedValue({
            ...summary,
            project_count: 0,
            active_flag_count: 0,
            production_enabled_count: 0,
            projects: [],
            recent_activity: [],
        });
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('Create your first project');
        expect(wrapper.findAll('a').some((link) => link.text().includes('Create a project'))).toBe(true);
    });

    it('keeps archived-project activity visible when no active projects remain', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockResolvedValue({
            ...summary,
            project_count: 0,
            active_flag_count: 0,
            production_enabled_count: 0,
            projects: [],
            recent_activity: [
                {
                    ...summary.recent_activity[0],
                    action: 'project.archived',
                    subject: { type: 'Project', id: 1, name: 'Checkout' },
                    environment: null,
                },
            ],
        });
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('No active projects');
        expect(wrapper.text()).toContain('Demo Owner archived project Checkout');
        expect(wrapper.text()).not.toContain('Create your first project');
        expect(wrapper.get('a[aria-label*="View Checkout audit history"]').attributes('href')).toBe(
            '/projects/1/audit-log',
        );
    });

    it('does not present stale values as current after retrieval failure', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockRejectedValue(new Error('Unavailable'));
        const wrapper = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('No previous values are shown as current');
        expect(wrapper.text()).not.toContain('Checkout');
        expect(wrapper.text()).not.toContain('Active projects');
    });

    it('distinguishes a successful response with no recent activity from account empty and failure states', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockResolvedValue({ ...summary, recent_activity: [] });
        const wrapper = await mountPage();

        expect(wrapper.text()).toContain('No recent changes');
        expect(wrapper.text()).not.toContain('Create your first project');
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    });

    it('retries a failed request and shows only the new authoritative response', async () => {
        const getSummary = vi
            .spyOn(dashboardService, 'getSummary')
            .mockRejectedValueOnce(new Error('Unavailable'))
            .mockResolvedValueOnce(summary);
        const wrapper = await mountPage();

        await wrapper.get('button').trigger('click');
        await flushPromises();

        expect(getSummary).toHaveBeenCalledTimes(2);
        expect(wrapper.text()).toContain('Checkout');
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    });

    it('aborts the active summary request when the page unmounts', async () => {
        let requestSignal: AbortSignal | undefined;
        vi.spyOn(dashboardService, 'getSummary').mockImplementation((signal) => {
            requestSignal = signal;
            return new Promise<DashboardSummary>(() => undefined);
        });
        const wrapper = await mountPage();

        wrapper.unmount();

        expect(requestSignal?.aborted).toBe(true);
    });
});
