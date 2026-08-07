import { describe, expect, it } from 'vitest';

import { safeRedirect } from './safeRedirect';

describe('safeRedirect', () => {
    it('allows internal dashboard routes', () => expect(safeRedirect('/projects/1')).toBe('/projects/1'));
    it.each(['https://example.com', '//example.com', '/%2Fexample.com', '/api/flags', '/dashboard'])(
        'falls back for unsafe destination %s',
        (destination) => expect(safeRedirect(destination)).toBe('/app'),
    );
});
