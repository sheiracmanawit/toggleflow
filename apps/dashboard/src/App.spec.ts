import { mount } from '@vue/test-utils';
import { createMemoryHistory, createRouter } from 'vue-router';
import { describe, expect, it } from 'vitest';

import App from './App.vue';
import { pinia, useAuthStore } from './stores';

describe('App', () => {
    it('renders accessible primary navigation', async () => {
        useAuthStore(pinia).resetForTests();
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/', component: { template: '<h1>Foundation</h1>' } }],
        });

        await router.push('/');
        await router.isReady();

        const wrapper = mount(App, { global: { plugins: [pinia, router] } });

        expect(wrapper.get('nav[aria-label="Primary"]').text()).toContain('ToggleFlow');
        expect(wrapper.get('main').text()).toContain('Foundation');
    });
});
