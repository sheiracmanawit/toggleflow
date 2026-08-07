import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { projectService } from '@features/projects';
import ProjectsPage from './ProjectsPage.vue';

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects', component: ProjectsPage },
            { path: '/projects/:projectId', component: { template: '<h1>Project</h1>' } },
        ],
    });
    await router.push('/projects');
    await router.isReady();
    const wrapper = mount(ProjectsPage, { attachTo: document.body, global: { plugins: [router] } });
    await flushPromises();
    return { router, wrapper };
};

describe('ProjectsPage', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    it('explains the empty state and creates a project without duplicate submission', async () => {
        vi.spyOn(projectService, 'list').mockResolvedValue([]);
        let finishCreate: ((value: Awaited<ReturnType<typeof projectService.create>>) => void) | undefined;
        const create = vi.spyOn(projectService, 'create').mockImplementation(
            () =>
                new Promise((resolve) => {
                    finishCreate = resolve;
                }),
        );
        const { router, wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Development, Staging, and Production are added automatically');
        await wrapper.get('button').trigger('click');
        expect(document.querySelector('[role="dialog"]')).not.toBeNull();
        expect(document.activeElement).toBe(document.querySelector('#project-name'));
        const name = document.querySelector<HTMLInputElement>('#project-name')!;
        const description = document.querySelector<HTMLTextAreaElement>('#project-description')!;
        const form = document.querySelector<HTMLFormElement>('form')!;
        name.value = 'Checkout Service';
        name.dispatchEvent(new Event('input'));
        description.value = 'Controls checkout releases.';
        description.dispatchEvent(new Event('input'));
        form.dispatchEvent(new Event('submit'));
        form.dispatchEvent(new Event('submit'));
        await flushPromises();

        expect(create).toHaveBeenCalledTimes(1);
        expect(create).toHaveBeenCalledWith({
            name: 'Checkout Service',
            slug: 'checkout-service',
            description: 'Controls checkout releases.',
        });
        expect(document.querySelector<HTMLButtonElement>('button[type="submit"]')?.disabled).toBe(true);

        finishCreate?.({
            id: 7,
            name: 'Checkout Service',
            slug: 'checkout-service',
            description: 'Controls checkout releases.',
            status: 'active',
            updated_at: '2026-07-23T00:00:00.000Z',
            environments: [],
        });
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/projects/7');
    });

    it('keeps entered information after a failed create', async () => {
        vi.spyOn(projectService, 'list').mockResolvedValue([]);
        vi.spyOn(projectService, 'create').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        const name = document.querySelector<HTMLInputElement>('#project-name')!;
        name.value = 'Payments';
        name.dispatchEvent(new Event('input'));
        document.querySelector<HTMLFormElement>('form')?.dispatchEvent(new Event('submit'));
        await flushPromises();

        expect(document.querySelector<HTMLInputElement>('#project-name')?.value).toBe('Payments');
        expect(document.querySelector('[role="alert"]')?.textContent).toContain(
            'entered information has been preserved',
        );
    });

    it('renders only project fields supplied by the backend', async () => {
        vi.spyOn(projectService, 'list').mockResolvedValue([
            {
                id: 1,
                name: 'Checkout',
                slug: 'checkout',
                description: null,
                status: 'active',
                updated_at: '2026-07-23T00:00:00.000Z',
            },
        ]);
        const { wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Checkout');
        expect(wrapper.text()).toContain('No project description yet.');
        expect(wrapper.text()).not.toContain('flags');
    });
});
