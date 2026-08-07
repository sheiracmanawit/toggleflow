import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

import { themePreferenceStorageKey } from './themePreference';

describe('initial theme application', () => {
    it('applies the shared storage contract before the application entry', () => {
        const html = readFileSync('index.html', 'utf8');
        const themeScript = html.indexOf(themePreferenceStorageKey);
        const applicationEntry = html.indexOf('/src/app/app.ts');

        expect(themeScript).toBeGreaterThan(-1);
        expect(themeScript).toBeLessThan(applicationEntry);
        expect(html).toContain("['light', 'dark', 'system']");
        expect(html).toContain("matchMedia('(prefers-color-scheme: dark)')");
        expect(html).toContain("classList.toggle('dark', resolved === 'dark')");
        expect(html).toContain('dataset.themePreference = preference');
    });
});
