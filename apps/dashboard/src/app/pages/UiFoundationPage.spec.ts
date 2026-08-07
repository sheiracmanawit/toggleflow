import { mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import UApp from '@nuxt/ui/components/App.vue';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import UiFoundationPage from './UiFoundationPage.vue';

describe('UI foundation fixture', () => {
    const mountFixture = async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/', component: UiFoundationPage }],
        });
        await router.push('/');
        await router.isReady();

        return mount(
            { components: { UApp, UiFoundationPage }, template: '<UApp><UiFoundationPage /></UApp>' },
            { global: { plugins: [ui, router], stubs: { UIcon: true } } },
        );
    };

    it('communicates environment and release state without color alone', async () => {
        const wrapper = await mountFixture();

        for (const label of ['Development', 'Staging', 'Production', 'Enabled', 'Disabled', 'Failed']) {
            expect(wrapper.text()).toContain(label);
        }
    });

    it('gives icon-only actions accessible names and associates form errors', async () => {
        const wrapper = await mountFixture();

        expect(wrapper.get('button[aria-label="Refresh current data"]')).toBeTruthy();
        expect(wrapper.get('button[aria-label="Open project actions"]')).toBeTruthy();
        expect(wrapper.text()).toContain('A unique flag key is required.');
        expect(wrapper.get('button').text()).toBeTruthy();
    });
});
