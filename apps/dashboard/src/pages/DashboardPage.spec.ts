import { flushPromises, mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { pinia, useAuthStore } from '../stores';
import DashboardPage from './DashboardPage.vue';

describe('DashboardPage', () => {
    const authStore = useAuthStore(pinia);

    afterEach(() => {
        vi.restoreAllMocks();
        authStore.resetForTests();
    });

    it('shows the current owner and signs out with replacement navigation', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Demo Owner', email: 'owner@toggleflow.test' };
        const logout = vi.spyOn(authStore, 'logout').mockResolvedValue();
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/app', component: DashboardPage },
                { path: '/sign-in', component: { template: '<h1>Sign in</h1>' } },
            ],
        });
        await router.push('/app');
        await router.isReady();
        const wrapper = mount(DashboardPage, { global: { plugins: [pinia, router] } });

        expect(wrapper.text()).toContain('Demo Owner');
        await wrapper.get('button').trigger('click');
        await flushPromises();

        expect(logout).toHaveBeenCalledOnce();
        expect(router.currentRoute.value.path).toBe('/sign-in');
    });
});
