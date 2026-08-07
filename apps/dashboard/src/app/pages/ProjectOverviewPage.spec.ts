import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { featureFlagService, type FeatureFlag } from '@features/feature-flags';
import { ProjectValidationError, projectService, useProjectContextStore, type Project } from '@features/projects';
import { pinia } from '@app/pinia';
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

const setField = (selector: string, value: string): void => {
    const field = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(selector)!;
    field.value = value;
    field.dispatchEvent(new Event('input'));
};

const submitDialog = (): void => document.querySelector<HTMLFormElement>('[role="dialog"] form')!.requestSubmit();

describe('ProjectOverviewPage', () => {
    const projectContextStore = useProjectContextStore(pinia);

    beforeEach(() => {
        projectContextStore.resetForTests();
        vi.spyOn(featureFlagService, 'list').mockResolvedValue([]);
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
        projectContextStore.resetForTests();
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
        setField('#edit-project-name', 'Checkout API');
        submitDialog();
        await flushPromises();

        expect(update).toHaveBeenCalledWith(1, {
            name: 'Checkout API',
            description: 'Checkout release controls',
        });
        expect(wrapper.get('h1').text()).toBe('Checkout API');
        expect(projectContextStore.projects[0]?.name).toBe('Checkout API');
    });

    it('compares each flag across all three environments with text labels', async () => {
        const flag: FeatureFlag = {
            id: 4,
            project_id: 1,
            name: 'New checkout',
            key: 'new-checkout',
            description: null,
            status: 'active',
            updated_at: '2026-07-23T00:00:00.000Z',
            environment_states: project.environments.map((environment) => ({
                environment,
                enabled: environment.key !== 'production',
                updated_at: '2026-07-23T00:00:00.000Z',
            })),
        };
        vi.mocked(featureFlagService.list).mockResolvedValue([flag]);
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        const { wrapper } = await mountPage();

        const table = wrapper.get('table');
        expect(table.text()).toContain('Development');
        expect(table.text()).toContain('Staging');
        expect(table.text()).toContain('Production');
        expect(table.text()).toContain('Enabled');
        expect(table.text()).toContain('Disabled');
        expect(table.text()).toContain('new-checkout');
    });

    it('shows missing environment state as not configured instead of disabled', async () => {
        const flag: FeatureFlag = {
            id: 4,
            project_id: 1,
            name: 'Partial checkout',
            key: 'partial-checkout',
            description: null,
            status: 'active',
            updated_at: '2026-07-23T00:00:00.000Z',
            environment_states: project.environments.slice(0, 2).map((environment) => ({
                environment,
                enabled: true,
                updated_at: '2026-07-23T00:00:00.000Z',
            })),
        };
        vi.mocked(featureFlagService.list).mockResolvedValue([flag]);
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        const { wrapper } = await mountPage();

        const mobileState = wrapper.get('[aria-label="Mobile release state"]');
        const productionState = mobileState.findAll('dl > div').find((state) => state.text().includes('Production'));
        expect(productionState?.text()).toContain('Not configured');
        expect(productionState?.text()).not.toContain('Disabled');
        expect(wrapper.get('table').text()).toContain('Not configured');
    });

    it('identifies an archived project and removes active-only mutation controls', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue({
            ...project,
            status: 'archived',
        });
        const update = vi.spyOn(projectService, 'update');
        const archive = vi.spyOn(projectService, 'archive');
        const { wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Archived');
        expect(wrapper.text()).toContain('environments and history remain available for reference');
        expect(wrapper.text()).not.toContain('Edit project');
        expect(wrapper.text()).not.toContain('Archive project');
        expect(update).not.toHaveBeenCalled();
        expect(archive).not.toHaveBeenCalled();
    });

    it('preserves confirmed project state when an update fails', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(projectService, 'update').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        setField('#edit-project-name', 'Unconfirmed name');
        submitDialog();
        await flushPromises();

        expect(wrapper.get('h1').text()).toBe('Checkout');
        expect(document.querySelector('[role="alert"]')?.textContent).toContain('last confirmed project information');
    });

    it('renders and associates description validation errors', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(projectService, 'update').mockRejectedValue(
            new ProjectValidationError({
                description: ['The description field must not be greater than 1000 characters.'],
            }),
        );
        const { wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        setField('#edit-project-description', 'x'.repeat(1001));
        submitDialog();
        await flushPromises();

        const description = document.querySelector<HTMLTextAreaElement>('#edit-project-description')!;
        expect(description.getAttribute('aria-invalid')).toBe('true');
        expect(description.getAttribute('aria-describedby')).toBe('edit-project-description-error');
        expect(document.querySelector('#edit-project-description-error')?.textContent).toContain(
            'must not be greater than 1000',
        );
    });

    it('clears project-specific edit state when the detail route changes', async () => {
        const secondProject: Project = {
            ...project,
            id: 2,
            name: 'Payments',
            slug: 'payments',
            description: 'Payments release controls',
        };
        vi.spyOn(projectService, 'get').mockImplementation(async (projectId) =>
            projectId === 1 ? project : secondProject,
        );
        const update = vi.spyOn(projectService, 'update');
        const { router, wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        setField('#edit-project-name', 'Stale checkout name');
        await router.push('/projects/2');
        await flushPromises();

        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
        expect(wrapper.get('h1').text()).toBe('Payments');

        await wrapper.get('button').trigger('click');
        expect(document.querySelector<HTMLInputElement>('#edit-project-name')?.value).toBe('Payments');
        expect(document.querySelector<HTMLTextAreaElement>('#edit-project-description')?.value).toBe(
            'Payments release controls',
        );
        expect(update).not.toHaveBeenCalled();
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

    it('keeps archive confirmation and later failure visible while archival is pending', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        let rejectArchive: ((reason: Error) => void) | undefined;
        vi.spyOn(projectService, 'archive').mockImplementation(
            () =>
                new Promise((_, reject) => {
                    rejectArchive = reject;
                }),
        );
        const { wrapper } = await mountPage();

        const archiveButton = wrapper.findAll('button').find((button) => button.text() === 'Archive project');
        await archiveButton?.trigger('click');
        const confirmButton = Array.from(document.body.querySelectorAll('button')).find(
            (button) => button.textContent === 'Archive project',
        );
        confirmButton?.click();
        await flushPromises();

        document.body
            .querySelector('[role="dialog"]')
            ?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();

        expect(document.body.querySelector('[role="dialog"]')).not.toBeNull();

        rejectArchive?.(new Error('Unavailable'));
        await flushPromises();

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
