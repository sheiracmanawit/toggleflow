import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { FeatureFlagValidationError, featureFlagService, projectService } from '../services';
import type { Project } from '../types';
import FeatureFlagsPage from './FeatureFlagsPage.vue';

const project: Project = {
    id: 1,
    name: 'Checkout',
    slug: 'checkout',
    description: null,
    status: 'active',
    updated_at: '2026-07-26T00:00:00Z',
    environments: [
        { id: 10, name: 'Development', key: 'development', color: '#2563eb' },
        { id: 11, name: 'Staging', key: 'staging', color: '#b45309' },
        { id: 12, name: 'Production', key: 'production', color: '#7c3aed' },
    ],
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects/:projectId/flags', component: FeatureFlagsPage },
            { path: '/projects/:projectId/flags/:flagId', component: { template: '<div>Details</div>' } },
        ],
    });
    await router.push('/projects/1/flags');
    await router.isReady();
    const wrapper = mount(FeatureFlagsPage, { global: { plugins: [router] } });
    await flushPromises();

    return { wrapper, router };
};

afterEach(() => vi.restoreAllMocks());

describe('FeatureFlagsPage', () => {
    it('explains the empty state and creates a disabled-by-default flag', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(featureFlagService, 'list').mockResolvedValue([]);
        vi.spyOn(featureFlagService, 'create').mockResolvedValue({
            id: 2,
            project_id: 1,
            name: 'New checkout',
            key: 'new-checkout',
            description: null,
            status: 'active',
            updated_at: '2026-07-26T00:00:00Z',
            environment_states: [],
        });
        const { wrapper, router } = await mountPage();

        expect(wrapper.text()).toContain('New flags begin disabled in Development, Staging, and Production');
        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Create flag')
            ?.trigger('click');
        await wrapper.get('#flag-name').setValue('New checkout');
        expect((wrapper.get('#flag-key').element as HTMLInputElement).value).toBe('new-checkout');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(featureFlagService.create).toHaveBeenCalledWith(1, {
            name: 'New checkout',
            key: 'new-checkout',
            description: '',
        });
        expect(router.currentRoute.value.path).toBe('/projects/1/flags/2');
    });

    it('shows a safe load failure without project context', async () => {
        vi.spyOn(projectService, 'get').mockRejectedValue(new Error('Forbidden'));
        vi.spyOn(featureFlagService, 'list').mockRejectedValue(new Error('Forbidden'));
        const { wrapper } = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('do not have access');
        expect(wrapper.text()).not.toContain('Checkout');
    });

    it('associates create validation messages with their inputs', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(featureFlagService, 'list').mockResolvedValue([]);
        vi.spyOn(featureFlagService, 'create').mockRejectedValue(
            new FeatureFlagValidationError({
                name: ['The name field is required.'],
                key: ['The key has already been taken.'],
                description: ['The description is too long.'],
            }),
        );
        const { wrapper } = await mountPage();

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Create flag')
            ?.trigger('click');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(wrapper.get('#flag-name').attributes('aria-describedby')).toBe('flag-name-error');
        expect(wrapper.get('#flag-key').attributes('aria-describedby')).toBe('flag-key-error');
        expect(wrapper.get('#flag-description').attributes('aria-describedby')).toBe('flag-description-error');
    });

    it('aligns populated state cells with environment headers by identity', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(featureFlagService, 'list').mockResolvedValue([
            {
                id: 2,
                project_id: 1,
                name: 'New checkout',
                key: 'new-checkout',
                description: null,
                status: 'active',
                updated_at: '2026-07-26T00:00:00Z',
                environment_states: [
                    {
                        environment: project.environments[2]!,
                        enabled: true,
                        updated_at: '2026-07-26T00:00:00Z',
                    },
                    {
                        environment: project.environments[1]!,
                        enabled: true,
                        updated_at: '2026-07-26T00:00:00Z',
                    },
                    {
                        environment: project.environments[0]!,
                        enabled: false,
                        updated_at: '2026-07-26T00:00:00Z',
                    },
                ],
            },
        ]);
        const { wrapper } = await mountPage();

        expect(wrapper.findAll('thead th').map((header) => header.text())).toEqual([
            'Flag',
            'Development',
            'Staging',
            'Production',
        ]);
        expect(wrapper.findAll('tbody td').map((cell) => cell.text())).toEqual([
            'New checkoutnew-checkout',
            'Disabled',
            'Enabled',
            'Enabled',
        ]);
    });
});
