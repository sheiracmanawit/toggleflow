import { describe, expect, it, vi } from 'vitest';

import {
    createThemePreferenceController,
    readThemePreference,
    resolveTheme,
    themePreferenceStorageKey,
} from './themePreference';

class MediaQueryStub {
    matches = false;
    listener: (() => void) | null = null;
    addEventListener = vi.fn((_event: string, listener: () => void) => {
        this.listener = listener;
    });
    removeEventListener = vi.fn((_event: string, listener: () => void) => {
        if (this.listener === listener) this.listener = null;
    });
    change(matches: boolean): void {
        this.matches = matches;
        this.listener?.();
    }
}

const createStorage = (initial?: string) => {
    const values = new Map<string, string>();
    if (initial !== undefined) values.set(themePreferenceStorageKey, initial);
    return {
        values,
        getItem: vi.fn((key: string) => values.get(key) ?? null),
        setItem: vi.fn((key: string, value: string) => values.set(key, value)),
    };
};

describe('theme preference', () => {
    it('defaults invalid and unavailable storage to System', () => {
        expect(readThemePreference(createStorage('unexpected'))).toBe('system');
        expect(
            readThemePreference({
                getItem: () => {
                    throw new Error('blocked');
                },
                setItem: vi.fn(),
            }),
        ).toBe('system');
    });

    it('resolves explicit and System preferences', () => {
        expect(resolveTheme('light', true)).toBe('light');
        expect(resolveTheme('dark', false)).toBe('dark');
        expect(resolveTheme('system', false)).toBe('light');
        expect(resolveTheme('system', true)).toBe('dark');
    });

    it('persists the preference and responds to system changes only in System mode', () => {
        const root = document.createElement('html');
        const storage = createStorage('system');
        const media = new MediaQueryStub();
        const controller = createThemePreferenceController(root, storage, media);

        controller.start();
        expect(media.addEventListener).toHaveBeenCalledOnce();
        media.change(true);
        expect(root.classList.contains('dark')).toBe(true);
        expect(root.style.colorScheme).toBe('dark');

        controller.setPreference('light');
        expect(storage.values.get(themePreferenceStorageKey)).toBe('light');
        expect(root.dataset.themePreference).toBe('light');
        expect(media.removeEventListener).toHaveBeenCalledOnce();
        media.change(true);
        expect(root.classList.contains('dark')).toBe(false);

        controller.stop();
    });

    it('restores a persisted explicit preference and tolerates write failures', () => {
        const root = document.createElement('html');
        const media = new MediaQueryStub();
        const storage = createStorage('dark');
        const restored = createThemePreferenceController(root, storage, media);
        restored.start();
        expect(restored.preference.value).toBe('dark');
        expect(root.classList.contains('dark')).toBe(true);

        const blocked = createThemePreferenceController(
            root,
            {
                getItem: () => 'system',
                setItem: () => {
                    throw new Error('blocked');
                },
            },
            media,
        );
        blocked.start();
        expect(() => blocked.setPreference('light')).not.toThrow();
        expect(blocked.resolvedTheme.value).toBe('light');
        blocked.stop();
    });
});
