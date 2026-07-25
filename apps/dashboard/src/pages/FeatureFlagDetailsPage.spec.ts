import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { featureFlagService } from '../services';
import type { FeatureFlag } from '../types';
import FeatureFlagDetailsPage from './FeatureFlagDetailsPage.vue';

const flag: FeatureFlag = {
    id: 2,
    project_id: 1,
    name: 'New checkout',
    key: 'new-checkout',
    description: 'Controls the new checkout.',
    status: 'active',
    updated_at: '2026-07-26T00:00:00Z',
    environment_states: [
        {
            environment: { id: 10, name: 'Development', key: 'development', color: '#2563eb' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
        {
            environment: { id: 11, name: 'Staging', key: 'staging', color: '#b45309' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
        {
            environment: { id: 12, name: 'Production', key: 'production', color: '#7c3aed' },
            enabled: false,
            updated_at: '2026-07-26T00:00:00Z',
        },
    ],
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects/:projectId/flags/:flagId', component: FeatureFlagDetailsPage },
            { path: '/projects/:projectId/flags', component: { template: '<div>Flags</div>' } },
        ],
    });
    await router.push('/projects/1/flags/2');
    await router.isReady();
    const wrapper = mount(FeatureFlagDetailsPage, {
        attachTo: document.body,
        global: { plugins: [router] },
    });
    await flushPromises();

    return { wrapper, router };
};

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('FeatureFlagDetailsPage', () => {
    it('requires explicit impact confirmation before changing Production', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'setState').mockResolvedValue({
            ...structuredClone(flag),
            environment_states: flag.environment_states.map((state) =>
                state.environment.key === 'production' ? { ...state, enabled: true } : state,
            ),
        });
        const { wrapper } = await mountPage();

        const productionSwitch = wrapper.get('[aria-label="Enable New checkout in Production"]');
        await productionSwitch.trigger('click');
        await flushPromises();

        const dialog = document.body.querySelector<HTMLElement>('[role="dialog"]');
        expect(dialog?.textContent).toContain('will begin receiving true');
        expect(dialog?.textContent).toContain('does not deploy application code');
        expect(featureFlagService.setState).not.toHaveBeenCalled();

        dialog?.querySelector<HTMLButtonElement>('button:last-child')?.click();
        await flushPromises();

        expect(featureFlagService.setState).toHaveBeenCalledWith(1, 2, 12, true);
        expect(wrapper.text()).toContain('Production is now enabled');
    });

    it('preserves the confirmed state and shows a persistent error when a mutation fails', async () => {
        vi.spyOn(featureFlagService, 'get').mockResolvedValue(structuredClone(flag));
        vi.spyOn(featureFlagService, 'setState').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper.get('[aria-label="Enable New checkout in Development"]').trigger('click');
        await flushPromises();

        expect(wrapper.get('[role="alert"]').text()).toContain('last confirmed state is still shown');
        expect(wrapper.get('[aria-label="Enable New checkout in Development"]').attributes('aria-checked')).toBe(
            'false',
        );
    });

    it('shows a safe unavailable state without stale flag details', async () => {
        vi.spyOn(featureFlagService, 'get').mockRejectedValue(new Error('Forbidden'));
        const { wrapper } = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('could not be found or you do not have access');
        expect(wrapper.text()).not.toContain('New checkout');
    });
});
