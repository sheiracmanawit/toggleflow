import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';

import AppDialog from './AppDialog.vue';

describe('AppDialog', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('moves focus inside, traps keyboard focus, supports Escape, and returns focus', async () => {
        const trigger = document.createElement('button');
        document.body.append(trigger);
        trigger.focus();
        const wrapper = mount(AppDialog, {
            attachTo: document.body,
            props: {
                title: 'Archive project?',
                description: 'The project will leave active views.',
            },
            slots: {
                default: '<button id="keep">Keep project</button><button id="archive">Archive project</button>',
            },
        });
        await flushPromises();

        expect(document.querySelector('[data-presentation="slideover"]')).not.toBeNull();
        expect(document.querySelector('[role="dialog"]')?.className).toContain('h-full');
        const keep = document.querySelector<HTMLButtonElement>('#keep');
        const archive = document.querySelector<HTMLButtonElement>('#archive');
        expect(document.activeElement).toBe(keep);

        archive?.focus();
        archive?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
        expect(document.activeElement).toBe(keep);

        keep?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        expect(wrapper.emitted('cancel')).toHaveLength(1);

        wrapper.unmount();
        expect(document.activeElement).toBe(trigger);
    });
});
