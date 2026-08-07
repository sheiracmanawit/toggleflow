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

        const skeletons = wrapper.get('[role="status"]').findAll('div');
        expect(skeletons).toHaveLength(3);
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
        expect(wrapper.get('a').text()).toContain('Create a project');
    });

    it('does not present stale values as current after retrieval failure', async () => {
        vi.spyOn(dashboardService, 'getSummary').mockRejectedValue(new Error('Unavailable'));
        const wrapper = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('No previous values are shown as current');
        expect(wrapper.text()).not.toContain('Checkout');
        expect(wrapper.text()).not.toContain('Active projects');
    });
});
