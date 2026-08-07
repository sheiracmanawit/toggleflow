import { describe, expect, it } from 'vitest';

import { toggleFlowAppConfig } from './app.config';

describe('ToggleFlow Nuxt UI configuration', () => {
    it('maps Nuxt UI roles to the approved semantic palette', () => {
        expect(toggleFlowAppConfig.ui.colors).toEqual({
            primary: 'teal',
            secondary: 'teal',
            success: 'emerald',
            info: 'sky',
            warning: 'amber',
            error: 'red',
            neutral: 'zinc',
        });
    });

    it('does not use violet as a general Nuxt UI interaction alias', () => {
        expect(Object.values(toggleFlowAppConfig.ui.colors)).not.toContain('violet');
    });

    it('uses one accessible Lucide icon convention', () => {
        expect(Object.values(toggleFlowAppConfig.ui.icons)).toEqual(
            expect.arrayContaining(['i-lucide-check', 'i-lucide-circle-x', 'i-lucide-info']),
        );
        expect(Object.values(toggleFlowAppConfig.ui.icons).every((icon) => icon.startsWith('i-lucide-'))).toBe(true);
    });
});
