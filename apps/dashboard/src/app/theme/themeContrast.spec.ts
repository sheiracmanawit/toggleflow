import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';

const luminance = (hex: string): number => {
    const channels = hex
        .slice(1)
        .match(/../g)!
        .map((channel) => Number.parseInt(channel, 16) / 255)
        .map((channel) => (channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4));
    return 0.2126 * channels[0]! + 0.7152 * channels[1]! + 0.0722 * channels[2]!;
};

const contrast = (foreground: string, background: string): number => {
    const values = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (values[0]! + 0.05) / (values[1]! + 0.05);
};

describe('cross-theme contrast', () => {
    const css = readFileSync('src/app/app.css', 'utf8');
    const light = css.match(/:root\s*\{(?<tokens>[\s\S]*?)\n\}/)?.groups?.tokens ?? '';
    const dark = css.match(/:root\.dark\s*\{(?<tokens>[\s\S]*?)\n\}/)?.groups?.tokens ?? '';
    const token = (theme: string, name: string): string =>
        theme.match(new RegExp(`--${name}:\\s*(#[0-9a-f]{6})`, 'i'))?.[1] ?? '';

    it('keeps text, focus, state, and environment colors readable on the structural surface', () => {
        const surface = token(dark, 'tf-surface');
        for (const name of [
            'tf-text',
            'tf-text-muted',
            'tf-focus',
            'tf-enabled',
            'tf-disabled',
            'tf-warning',
            'tf-danger',
            'tf-development',
            'tf-staging',
            'tf-production',
        ]) {
            expect(contrast(token(dark, name), surface), name).toBeGreaterThanOrEqual(4.5);
        }
        expect(contrast(token(dark, 'tf-border'), surface)).toBeGreaterThanOrEqual(3);
    });

    it('uses accessible teal/mint primary treatments in both presentations', () => {
        expect(contrast('#ffffff', token(light, 'ui-color-primary-500'))).toBeGreaterThanOrEqual(4.5);
        expect(contrast(token(light, 'tf-brand'), '#ffffff')).toBeGreaterThanOrEqual(4.5);
        expect(contrast('#18181b', token(dark, 'ui-color-primary-500'))).toBeGreaterThanOrEqual(4.5);
    });

    it('keeps primary, emerald Enabled, and violet Production distinct', () => {
        for (const theme of [light, dark]) {
            expect(token(theme, 'tf-brand')).not.toBe(token(theme, 'tf-enabled'));
            expect(token(theme, 'tf-brand')).not.toBe(token(theme, 'tf-production'));
            expect(token(theme, 'tf-enabled')).not.toBe(token(theme, 'tf-production'));
        }
    });
});
