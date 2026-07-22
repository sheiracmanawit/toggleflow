import { createMemoryHistory, createRouter } from 'vue-router';
import { beforeEach, describe, expect, it } from 'vitest';

import { pinia, useAuthStore } from '../stores';
import { authenticationGuard, routes, safeRedirect } from './index';

describe('dashboard routing', () => {
    const authStore = useAuthStore(pinia);

    beforeEach(() => {
        authStore.resetForTests();
        authStore.status = 'guest';
    });

    it('redirects protected routes to sign in and preserves the destination', async () => {
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/app');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/sign-in');
        expect(router.currentRoute.value.query.redirect).toBe('/app');
    });

    it('redirects authenticated owners away from sign in', async () => {
        authStore.status = 'authenticated';
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/sign-in');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/app');
    });

    it('allows public routes without redirecting', async () => {
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/');
    });

    it.each([
        ['https://evil.example', '/app'],
        ['//evil.example', '/app'],
        ['/%2F%2Fevil.example', '/app'],
        ['/dashboard/auth/session', '/app'],
        ['/api/v1/flags/example', '/app'],
        ['/sanctum/csrf-cookie', '/app'],
        ['/app?project=1', '/app?project=1'],
    ])('normalizes redirect %s to %s', (candidate, expected) => {
        expect(safeRedirect(candidate)).toBe(expected);
    });
});
