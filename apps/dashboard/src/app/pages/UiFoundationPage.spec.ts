import { flushPromises, mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import UApp from '@nuxt/ui/components/App.vue';
import { afterEach, describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';

import UiFoundationPage from './UiFoundationPage.vue';

describe('UI foundation fixture', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    const mountFixture = async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/', component: UiFoundationPage }],
        });
        await router.push('/');
        await router.isReady();

        return mount(
            { components: { UApp, UiFoundationPage }, template: '<UApp><UiFoundationPage /></UApp>' },
            { attachTo: document.body, global: { plugins: [ui, router], stubs: { UIcon: true } } },
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
        const invalidInput = wrapper.get('input[value="new checkout"]');
        const errorId = invalidInput.attributes('aria-describedby');
        expect(errorId).toBeTruthy();
        expect(wrapper.get(`#${errorId}`).text()).toContain('A unique flag key is required.');
    });

    it('opens and dismisses overlays and restores focus', async () => {
        const wrapper = await mountFixture();
        const confirmation = wrapper.findAll('button').find((button) => button.text().includes('Open confirmation'))!;
        confirmation.element.focus();

        await confirmation.trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('Enable new-checkout in Production?');
        const modal = document.querySelector('[role="dialog"]');
        expect(modal).toBeTruthy();
        expect(modal?.contains(document.activeElement)).toBe(true);

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();
        await new Promise((resolve) => setTimeout(resolve, 250));
        expect(document.body.textContent).not.toContain('This change does not deploy application code.');
        expect(document.activeElement).toBe(confirmation.element);

        const slideover = wrapper.findAll('button').find((button) => button.text().includes('Open mobile panel'))!;
        await slideover.trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('Responsive navigation fixture');
        const panel = document.querySelector('[role="dialog"]');
        expect(panel).toBeTruthy();
        expect(panel?.contains(document.activeElement)).toBe(true);
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();
        expect(document.body.textContent).not.toContain('Responsive navigation fixture');
    });

    it('exercises menu, tooltip, toast, table, and pagination behavior', async () => {
        const wrapper = await mountFixture();

        const menu = wrapper.get('button[aria-label="Open project actions"]');
        await menu.trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('Rename');
        expect(document.body.textContent).toContain('Archive');
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
        await flushPromises();
        expect(document.activeElement?.textContent).toContain('Rename');
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();
        expect(document.body.textContent).not.toContain('Archive');

        const tooltip = wrapper.get('button[aria-label="Refresh current data"]');
        await tooltip.trigger('mouseenter');
        await tooltip.trigger('focus');
        await flushPromises();
        expect(document.body.textContent).toContain('Refresh current data');
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await flushPromises();

        const toast = wrapper.findAll('button').find((button) => button.text().includes('Show toast'))!;
        await toast.trigger('click');
        await flushPromises();
        expect(document.body.textContent).toContain('The confirmed state is current.');
        expect(document.querySelector('[aria-label="Notifications (F8)"]')).toBeTruthy();

        expect(wrapper.get('table').attributes('role')).not.toBe('presentation');
        expect(wrapper.findAll('th').length).toBeGreaterThan(0);
        const pageTwo = wrapper
            .findAll('button')
            .find((button) => button.attributes('aria-label')?.includes('Page 2'))!;
        await pageTwo.trigger('click');
        expect(wrapper.text()).toContain('Showing fixture page 2.');
    });
});
