import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { apiKeyService } from '@features/credentials';
import { projectService } from '@features/projects';
import type { ApiKey } from '@features/credentials';
import type { Project } from '@features/projects';
import ApiKeysPage from './ApiKeysPage.vue';

const project: Project = {
    id: 1,
    name: 'Checkout',
    slug: 'checkout',
    description: null,
    status: 'active',
    updated_at: '2026-07-29T00:00:00Z',
    environments: [
        { id: 10, name: 'Development', key: 'development', color: '#2563eb' },
        { id: 11, name: 'Staging', key: 'staging', color: '#b45309' },
        { id: 12, name: 'Production', key: 'production', color: '#7c3aed' },
    ],
};

const activeKey: ApiKey = {
    id: 20,
    name: 'Checkout production',
    prefix: 'abcd1234',
    state: 'active',
    created_at: '2026-07-29T00:00:00Z',
    last_used_at: null,
    revoked_at: null,
    environment: project.environments[2]!,
};

const secondProject: Project = {
    ...project,
    id: 2,
    name: 'Payments',
    slug: 'payments',
    environments: project.environments.map((environment) => ({
        ...environment,
        id: environment.id + 10,
    })),
};

const mountPage = async () => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/projects/:projectId', component: { template: '<div>Project</div>' } },
            { path: '/projects/:projectId/api-keys', component: ApiKeysPage },
        ],
    });
    await router.push('/projects/1/api-keys');
    await router.isReady();
    const wrapper = mount(ApiKeysPage, {
        attachTo: document.body,
        global: { plugins: [router] },
    });
    await flushPromises();

    return { router, wrapper };
};

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('ApiKeysPage', () => {
    it('groups safe credential metadata by environment', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(apiKeyService, 'list').mockResolvedValue([activeKey]);
        const { wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Development');
        expect(wrapper.text()).toContain('Staging');
        expect(wrapper.text()).toContain('Production');
        expect(wrapper.text()).toContain('tf_env_abcd1234_…');
        expect(wrapper.text()).not.toContain('complete-secret');
    });

    it('shows a credential once and requires acknowledgement before dismissal', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(apiKeyService, 'list').mockResolvedValue([]);
        vi.spyOn(apiKeyService, 'issue').mockResolvedValue({
            apiKey: activeKey,
            credential: 'tf_env_abcd1234_complete-secret',
        });
        const { wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        await wrapper.get('#api-key-name').setValue('Checkout production');
        await wrapper.get('#api-key-environment').setValue('12');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        const dialog = document.body.querySelector<HTMLElement>('[role="dialog"]');
        expect(dialog?.textContent).toContain('tf_env_abcd1234_complete-secret');
        const done = Array.from(dialog?.querySelectorAll<HTMLButtonElement>('button') ?? []).find(
            (button) => button.textContent?.trim() === 'Done',
        );
        expect(done?.disabled).toBe(true);

        const checkbox = dialog?.querySelector<HTMLInputElement>('input[type="checkbox"]');
        checkbox?.click();
        await flushPromises();
        expect(done?.disabled).toBe(false);
        done?.click();
        await flushPromises();

        expect(document.body.textContent).not.toContain('tf_env_abcd1234_complete-secret');
        expect(wrapper.text()).toContain('cannot display it again');
    });

    it('preserves active state and confirmation when revocation fails', async () => {
        vi.spyOn(projectService, 'get').mockResolvedValue(project);
        vi.spyOn(apiKeyService, 'list').mockResolvedValue([activeKey]);
        vi.spyOn(apiKeyService, 'revoke').mockRejectedValue(new Error('Unavailable'));
        const { wrapper } = await mountPage();

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'Revoke')
            ?.trigger('click');
        await flushPromises();
        const confirm = Array.from(document.body.querySelectorAll<HTMLButtonElement>('button')).find(
            (button) => button.textContent === 'Revoke API key',
        );
        confirm?.click();
        await flushPromises();

        expect(document.body.textContent).toContain('last confirmed state is still shown');
        expect(document.body.textContent).toContain('immediately lose evaluation access');
        expect(wrapper.text()).toContain('Active');
    });

    it('clears a visible one-time credential when the project route changes', async () => {
        vi.spyOn(projectService, 'get').mockImplementation(async (projectId) =>
            projectId === 1 ? project : secondProject,
        );
        vi.spyOn(apiKeyService, 'list').mockResolvedValue([]);
        vi.spyOn(apiKeyService, 'issue').mockResolvedValue({
            apiKey: activeKey,
            credential: 'tf_env_abcd1234_complete-secret',
        });
        const { router, wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        await wrapper.get('#api-key-name').setValue('Checkout production');
        await wrapper.get('form').trigger('submit');
        await flushPromises();
        expect(document.body.textContent).toContain('tf_env_abcd1234_complete-secret');

        await router.push('/projects/2/api-keys');
        await flushPromises();

        expect(wrapper.text()).toContain('Payments');
        expect(document.body.textContent).not.toContain('tf_env_abcd1234_complete-secret');
        expect(document.body.querySelector('[role="dialog"]')).toBeNull();
    });

    it('discards a late issuance response after the project route changes', async () => {
        vi.spyOn(projectService, 'get').mockImplementation(async (projectId) =>
            projectId === 1 ? project : secondProject,
        );
        vi.spyOn(apiKeyService, 'list').mockResolvedValue([]);
        let resolveIssue: ((value: { apiKey: ApiKey; credential: string }) => void) | undefined;
        vi.spyOn(apiKeyService, 'issue').mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveIssue = resolve;
                }),
        );
        const { router, wrapper } = await mountPage();

        await wrapper.get('button').trigger('click');
        await wrapper.get('#api-key-name').setValue('Checkout production');
        await wrapper.get('form').trigger('submit');
        await router.push('/projects/2/api-keys');
        await flushPromises();

        resolveIssue?.({
            apiKey: activeKey,
            credential: 'tf_env_abcd1234_late-secret',
        });
        await flushPromises();

        expect(wrapper.text()).toContain('Payments');
        expect(document.body.textContent).not.toContain('tf_env_abcd1234_late-secret');
        expect(wrapper.text()).not.toContain('Checkout production');
    });
});
