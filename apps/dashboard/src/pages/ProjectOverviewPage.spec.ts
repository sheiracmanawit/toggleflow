import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { projectService } from '../services';
import type { Project } from '../types';
import ProjectOverviewPage from './ProjectOverviewPage.vue';

const project: Project = {
    id: 1,
    name: 'Checkout',
    slug: 'checkout',
    description: 'Checkout release controls',
    status: 'active',
    updated_at: '2026-07-23T00:00:00.000Z',
    environments: [
        { id: 1, name: 'Development', key: 'development', color: '#2563eb' },
        { id: 2, name: 'Staging', key: 'staging', color: '#b45309' },
        { id: 3, name: 'Production', key: 'production', color: '#7c3aed' },
    ],
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects', component: { template: '<h1>Projects</h1>' } },
            { path: '/projects/:projectId', component: ProjectOverviewPage },
        ],
    });
    await router.push('/projects/1');
    await router.isReady();
    const wrapper = mount(ProjectOverviewPage, {
        attachTo: document.body,
        global: { plugins: [router] },
    });
    await flushPromises();
    return { router, wrapper };
};

describe('ProjectOverviewPage', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    it('shows fixed environments with text and saves server-confirmed metadata', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        const update = vi.spyOn(projectService, 'update').mockResolvedValue({
            ...project,
            name: 'Checkout API',
        });
        const { wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Development');
        expect(wrapper.text()).toContain('Staging');
        expect(wrapper.text()).toContain('Production');
        await wrapper.get('button').trigger('click');
        await wrapper.get('#edit-project-name').setValue('Checkout API');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(update).toHaveBeenCalledWith(1, {
            name: 'Checkout API',
            description: 'Checkout release controls',
        });
        expect(wrapper.get('h1').text()).toBe('Checkout API');
    });

    it('preserves confirmed project state when an update fails', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(projectService, 'update').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        await wrapper.get('#edit-project-name').setValue('Unconfirmed name');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(wrapper.get('h1').text()).toBe('Checkout');
        expect(wrapper.get('[role="alert"]').text()).toContain('last confirmed project information');
    });

    it('requires confirmation and retains the project when archival fails', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(projectService, 'archive').mockRejectedValue(new Error('Unavailable'));
        const { router, wrapper } = await mountPage();

        const archiveButton = wrapper.findAll('button').find((button) => button.text() === 'Archive project');
        await archiveButton?.trigger('click');
        await flushPromises();

        expect(document.body.querySelector('[role="dialog"]')).not.toBeNull();
        const confirmButton = Array.from(document.body.querySelectorAll('button')).find(
            (button) => button.textContent === 'Archive project',
        );
        confirmButton?.click();
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/projects/1');
        expect(document.body.textContent).toContain('The project was not archived');
        expect(wrapper.get('h1').text()).toBe('Checkout');
    });

    it('uses the same safe unavailable state for a rejected project load', async () => {
        vi.spyOn(projectService, 'get').mockRejectedValue(new Error('Not found'));
        const { wrapper } = await mountPage();

        expect(wrapper.get('[role="alert"]').text()).toContain('could not be found or you do not have access to it');
        expect(wrapper.text()).not.toContain('Checkout release controls');
    });
});
