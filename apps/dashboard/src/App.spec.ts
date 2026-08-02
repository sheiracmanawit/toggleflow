import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import App from './App.vue';
import { projectService } from './services';
import { pinia, useAuthStore } from './stores';

describe('App', () => {
    const authStore = useAuthStore(pinia);

    beforeEach(() => {
        vi.spyOn(projectService, 'list').mockResolvedValue([]);
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
        authStore.resetForTests();
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
    });

    it('moves focus into the mobile drawer and returns it after Escape', async () => {
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
        expect(document.activeElement?.getAttribute('aria-label')).toBe('Mobile application navigation');

        await wrapper.get('aside[aria-label="Mobile application navigation"]').trigger('keydown', { key: 'Escape' });
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
