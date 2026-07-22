import { createMemoryHistory, createRouter } from 'vue-router';
import { describe, expect, it } from 'vitest';

import { authenticationGuard, routes } from './index';

describe('dashboard routing', () => {
    it('redirects protected routes to sign in and preserves the destination', async () => {
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/dashboard');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/sign-in');
        expect(router.currentRoute.value.query.redirect).toBe('/dashboard');
    });

    it('allows public routes without redirecting', async () => {
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/');
    });
});
