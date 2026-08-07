import { mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import { beforeEach, describe, expect, it } from 'vitest';

import ThemePreferenceSelector from './ThemePreferenceSelector.vue';
import { themePreferenceController, themePreferenceStorageKey } from '../theme/themePreference';

describe('ThemePreferenceSelector', () => {
    beforeEach(() => {
        localStorage.clear();
        themePreferenceController.setPreference('system');
    });

    it('exposes three named choices and their selected state', async () => {
        const wrapper = mount(ThemePreferenceSelector, { global: { plugins: [ui], stubs: { UIcon: true } } });
        const light = wrapper.get('button[aria-label="Use Light theme"]');
        const dark = wrapper.get('button[aria-label="Use Dark theme"]');
        const system = wrapper.get('button[aria-label="Use System theme"]');

        expect(system.attributes('aria-pressed')).toBe('true');
        expect(light.attributes('aria-pressed')).toBe('false');
        await dark.trigger('click');
        expect(dark.attributes('aria-pressed')).toBe('true');
        expect(localStorage.getItem(themePreferenceStorageKey)).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});
