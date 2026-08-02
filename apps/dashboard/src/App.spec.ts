import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import App from './App.vue';
import { projectService } from './services';
import { pinia, useAuthStore, useProjectContextStore } from './stores';

describe('App', () => {
    const authStore = useAuthStore(pinia);
    const projectContextStore = useProjectContextStore(pinia);

    beforeEach(() => {
        projectContextStore.resetForTests();
        vi.spyOn(projectService, 'list').mockResolvedValue([]);
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
        authStore.resetForTests();
        projectContextStore.resetForTests();
    });

    it('renders only implemented application and project navigation', async () => {
        vi.mocked(projectService.list).mockResolvedValue([
            {
                id: 1,
                name: 'Checkout',
                slug: 'checkout',
                description: null,
                status: 'active',
                updated_at: '2026-08-02T00:00:00.000Z',
            },
        ]);
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/app', component: { template: '<h1>Dashboard</h1>' } },
                { path: '/projects', component: { template: '<h1>Projects</h1>' } },
                { path: '/projects/:projectId', component: { template: '<h1>Project</h1>' } },
                { path: '/projects/:projectId/flags', component: { template: '<h1>Flags</h1>' } },
                { path: '/projects/:projectId/api-keys', component: { template: '<h1>API keys</h1>' } },
                { path: '/sign-in', component: { template: '<h1>Sign in</h1>' } },
            ],
        });
        await router.push('/projects/1');
        await router.isReady();
        const wrapper = mount(App, { global: { plugins: [pinia, router] } });
        await flushPromises();

        const navigation = wrapper.get('nav[aria-label="Application"]').text();
        expect(wrapper.get('#project-switcher').element).toHaveProperty('value', '1');
        expect(navigation).toContain('Checkout');
        expect(navigation).toContain('Project overview');
        expect(navigation).toContain('Feature flags');
        expect(navigation).toContain('API keys');
        expect(navigation).not.toContain('Audit log');
        expect(navigation).not.toContain('Settings');
    });

    it('switches project context and withholds links for an unknown project', async () => {
        vi.mocked(projectService.list).mockResolvedValue([
            {
                id: 2,
                name: 'Payments',
                slug: 'payments',
                description: null,
                status: 'active',
                updated_at: '2026-08-02T00:00:00.000Z',
            },
        ]);
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/projects/:projectId', component: { template: '<h1>Project</h1>' } },
                { path: '/projects/:projectId/flags', component: { template: '<h1>Flags</h1>' } },
                { path: '/sign-in', component: { template: '<h1>Sign in</h1>' } },
            ],
        });
        await router.push('/projects/99');
        await router.isReady();
        const wrapper = mount(App, { global: { plugins: [pinia, router] } });
        await flushPromises();

        expect(wrapper.get('nav[aria-label="Application"]').text()).not.toContain('Project overview');
        await wrapper.get('#project-switcher').setValue('2');
        await flushPromises();
        expect(router.currentRoute.value.path).toBe('/projects/2');
        expect(wrapper.get('nav[aria-label="Application"]').text()).toContain('Project overview');

        projectContextStore.updateProject({
            ...projectContextStore.projects[0],
            name: 'Payments API',
        });
        await router.push('/projects/2/flags');
        await flushPromises();
        expect(wrapper.get('nav[aria-label="Application"]').text()).toContain('Payments API');
        expect(wrapper.get('#project-switcher').text()).toContain('Payments API');
    });

    it('contains focus in the mobile drawer and returns it after Escape', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/app', component: { template: '<h1>Dashboard</h1>' } }],
        });
        await router.push('/app');
        await router.isReady();
        const wrapper = mount(App, { attachTo: document.body, global: { plugins: [pinia, router] } });

        const openButton = wrapper.get('button[aria-label="Open navigation"]');
        await openButton.trigger('click');
        await flushPromises();
        const drawer = wrapper.get('aside[aria-label="Mobile application navigation"]');
        const closeButton = drawer.findAll('button').find((button) => button.text() === 'Close');
        const signOutButton = drawer.findAll('button').find((button) => button.text() === 'Sign out');
        expect(document.activeElement).toBe(closeButton?.element);
        expect(wrapper.get('header').attributes()).toHaveProperty('inert');

        await closeButton?.trigger('keydown', { key: 'Tab', shiftKey: true });
        expect(document.activeElement).toBe(signOutButton?.element);
        await signOutButton?.trigger('keydown', { key: 'Tab' });
        expect(document.activeElement).toBe(closeButton?.element);

        await drawer.trigger('keydown', { key: 'Escape' });
        await flushPromises();
        expect(document.activeElement).toBe(openButton.element);
    });

    it('signs out from the shared shell', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        vi.spyOn(authStore, 'logout').mockResolvedValue();
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/app', component: { template: '<h1>Dashboard</h1>' } },
                { path: '/sign-in', component: { template: '<h1>Sign in</h1>' } },
            ],
        });
        await router.push('/app');
        await router.isReady();
        const wrapper = mount(App, { global: { plugins: [pinia, router] } });

        await wrapper.get('button:not([aria-label])').trigger('click');
        await flushPromises();
        expect(authStore.logout).toHaveBeenCalledOnce();
        expect(router.currentRoute.value.path).toBe('/sign-in');
    });
});
