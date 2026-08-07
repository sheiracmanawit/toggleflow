import { readonly, ref, type Ref } from 'vue';

export const themePreferenceStorageKey = 'toggleflow.theme-preference';
export const themePreferences = ['light', 'dark', 'system'] as const;

export type ThemePreference = (typeof themePreferences)[number];
export type ResolvedTheme = Exclude<ThemePreference, 'system'>;

type ThemeStorage = Pick<Storage, 'getItem' | 'setItem'>;
type ThemeMediaQuery = Pick<MediaQueryList, 'matches' | 'addEventListener' | 'removeEventListener'>;

export const isThemePreference = (value: unknown): value is ThemePreference =>
    typeof value === 'string' && themePreferences.includes(value as ThemePreference);

export const resolveTheme = (preference: ThemePreference, systemPrefersDark: boolean): ResolvedTheme =>
    preference === 'system' ? (systemPrefersDark ? 'dark' : 'light') : preference;

export const readThemePreference = (storage: ThemeStorage): ThemePreference => {
    try {
        const stored = storage.getItem(themePreferenceStorageKey);
        return isThemePreference(stored) ? stored : 'system';
    } catch {
        return 'system';
    }
};

export interface ThemePreferenceController {
    preference: Readonly<Ref<ThemePreference>>;
    resolvedTheme: Readonly<Ref<ResolvedTheme>>;
    setPreference: (preference: ThemePreference) => void;
    start: () => void;
    stop: () => void;
}

export const createThemePreferenceController = (
    root: HTMLElement,
    storage: ThemeStorage,
    mediaQuery: ThemeMediaQuery,
): ThemePreferenceController => {
    const preference = ref<ThemePreference>('system');
    const resolvedTheme = ref<ResolvedTheme>('light');
    let observingSystem = false;

    const apply = (): void => {
        resolvedTheme.value = resolveTheme(preference.value, mediaQuery.matches);
        root.classList.toggle('dark', resolvedTheme.value === 'dark');
        root.style.colorScheme = resolvedTheme.value;
        root.dataset.themePreference = preference.value;
    };
    const onSystemChange = (): void => apply();
    const updateObservation = (): void => {
        const shouldObserve = preference.value === 'system';
        if (shouldObserve === observingSystem) return;
        if (shouldObserve) mediaQuery.addEventListener('change', onSystemChange);
        else mediaQuery.removeEventListener('change', onSystemChange);
        observingSystem = shouldObserve;
    };
    const setPreference = (nextPreference: ThemePreference): void => {
        preference.value = nextPreference;
        try {
            storage.setItem(themePreferenceStorageKey, nextPreference);
        } catch {
            // Presentation still updates when storage is unavailable.
        }
        updateObservation();
        apply();
    };
    const start = (): void => {
        preference.value = readThemePreference(storage);
        updateObservation();
        apply();
    };
    const stop = (): void => {
        if (observingSystem) mediaQuery.removeEventListener('change', onSystemChange);
        observingSystem = false;
    };

    return { preference: readonly(preference), resolvedTheme: readonly(resolvedTheme), setPreference, start, stop };
};

const browserStorage: ThemeStorage = {
    getItem: (key) => window.localStorage.getItem(key),
    setItem: (key, value) => window.localStorage.setItem(key, value),
};

export const themePreferenceController = createThemePreferenceController(
    document.documentElement,
    browserStorage,
    window.matchMedia('(prefers-color-scheme: dark)'),
);
