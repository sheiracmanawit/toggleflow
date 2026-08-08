import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import App from './App.vue';
import { projectService } from '@features/projects';
import { useAuthStore } from '@features/authentication';
import { useProjectContextStore } from '@features/projects';
import { pinia } from './pinia';

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
        expect(wrapper.get('nav[aria-label="Primary"]').text()).not.toContain('Dashboard');
        expect(wrapper.get('a[aria-current="page"]').text()).toContain('Project overview');
        expect(wrapper.get('a[aria-current="page"]').text()).toContain('(current)');
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
        await openButton.trigger('keydown', { key: 'Enter' });
        await flushPromises();
        const drawer = wrapper.get('aside[aria-label="Mobile application navigation"]');
        const closeButton = drawer.find('button[aria-label="Close navigation"]');
        const signOutButton = drawer.findAll('button').find((button) => button.text() === 'Sign out');
        expect(document.activeElement).toBe(closeButton.element);
        expect(document.body.style.overflow).toBe('hidden');
        expect(wrapper.get('header').attributes()).toHaveProperty('inert');

        await closeButton.trigger('keydown', { key: 'Tab', shiftKey: true });
        expect(document.activeElement).toBe(signOutButton?.element);
        await signOutButton?.trigger('keydown', { key: 'Tab' });
        expect(document.activeElement).toBe(closeButton?.element);

        await drawer.trigger('keydown', { key: 'Escape' });
        await flushPromises();
        expect(document.activeElement).toBe(openButton.element);
        expect(document.body.style.overflow).toBe('');
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

        await wrapper.get('summary[aria-label="Open user menu for Demo Owner"]').trigger('click');
        expect(wrapper.get('details').attributes()).toHaveProperty('open');
        await wrapper.get('[data-testid="desktop-sign-out"]').trigger('click');
        await flushPromises();
        expect(authStore.logout).toHaveBeenCalledOnce();
        expect(router.currentRoute.value.path).toBe('/sign-in');
    });

    it('dismisses the desktop user menu and restores focus with Escape or an outside click', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/app', component: { template: '<h1>Dashboard</h1>' } }],
        });
        await router.push('/app');
        await router.isReady();
        const wrapper = mount(App, { attachTo: document.body, global: { plugins: [pinia, router] } });
        const menuButton = wrapper.get('summary[aria-label="Open user menu for Demo Owner"]');
        const menu = wrapper.get('details');

        await menuButton.trigger('click');
        (menuButton.element as globalThis.HTMLElement).focus();
        document.dispatchEvent(new globalThis.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();
        expect(menu.attributes()).not.toHaveProperty('open');
        expect(document.activeElement).toBe(menuButton.element);

        await menuButton.trigger('click');
        document.body.dispatchEvent(new globalThis.PointerEvent('pointerdown', { bubbles: true }));
        await flushPromises();
        expect(menu.attributes()).not.toHaveProperty('open');
    });

    it('collapses and expands the desktop sidebar without removing navigation', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@example.test' };
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/app', component: { template: '<h1>Dashboard</h1>' } }],
        });
        await router.push('/app');
        await router.isReady();
        const wrapper = mount(App, { global: { plugins: [pinia, router] } });

        await wrapper.get('button[aria-label="Hide sidebar"]').trigger('click');
        expect(wrapper.get('button[aria-label="Show sidebar"]')).toBeTruthy();
        expect(wrapper.get('nav[aria-label="Application"] a').attributes('title')).toBe('Overview');

        await wrapper.get('button[aria-label="Show sidebar"]').trigger('click');
        expect(wrapper.get('button[aria-label="Hide sidebar"]')).toBeTruthy();
    });
});
