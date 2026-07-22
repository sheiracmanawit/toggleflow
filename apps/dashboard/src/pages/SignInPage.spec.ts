import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { authService } from '../services/auth';
import { authStore } from '../stores/auth';
import SignInPage from './SignInPage.vue';

const mountPage = async (path = '/sign-in') => {
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/sign-in', component: SignInPage },
            { path: '/app', component: { template: '<h1>Dashboard</h1>' } },
        ],
    });
    await router.push(path);
    await router.isReady();
    const wrapper = mount(SignInPage, { global: { plugins: [router] } });
    await flushPromises();
    return { router, wrapper };
};

describe('SignInPage', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        authStore.resetForTests();
    });

    it('associates client validation errors with persistent labels', async () => {
        vi.spyOn(authService, 'demoCredentials').mockResolvedValue(null);
        const { wrapper } = await mountPage();

        await wrapper.get('form').trigger('submit');

        expect(wrapper.get('label[for="email"]').text()).toBe('Email address');
        expect(wrapper.get('#email').attributes('aria-describedby')).toBe('email-error');
        expect(wrapper.get('#password').attributes('aria-describedby')).toBe('password-error');
        expect(wrapper.get('[role="alert"]').text()).toContain('Check the highlighted fields');
    });

    it('prevents duplicate submission and restores the intended safe destination', async () => {
        vi.spyOn(authService, 'demoCredentials').mockResolvedValue(null);
        let finishLogin: (() => void) | undefined;
        const login = vi.spyOn(authStore, 'login').mockImplementation(
            () =>
                new Promise<void>((resolve) => {
                    finishLogin = resolve;
                }),
        );
        const { router, wrapper } = await mountPage('/sign-in?redirect=/app');

        await wrapper.get('#email').setValue('owner@example.com');
        await wrapper.get('#password').setValue('correct-password');
        await wrapper.get('form').trigger('submit');

        expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
        expect(wrapper.get('button[type="submit"]').text()).toContain('Signing in');
        expect(login).toHaveBeenCalledTimes(1);

        finishLogin?.();
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/app');
    });

    it('shows credentials only when the backend enables demo mode', async () => {
        vi.spyOn(authService, 'demoCredentials').mockResolvedValue({
            email: 'owner@toggleflow.test',
            password: 'toggleflow-demo',
        });
        const { wrapper } = await mountPage();

        expect(wrapper.text()).toContain('Local demo account');
        expect(wrapper.text()).toContain('owner@toggleflow.test');
        expect(wrapper.text()).toContain('toggleflow-demo');
    });
});
