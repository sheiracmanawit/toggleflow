import { createMemoryHistory, createRouter } from 'vue-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { authService } from '../services/auth';
import { pinia, useAuthStore } from '../stores';
import { authenticationGuard, routes, safeRedirect } from './index';

describe('dashboard routing', () => {
    const authStore = useAuthStore(pinia);

    beforeEach(() => {
        vi.restoreAllMocks();
        authStore.resetForTests();
        authStore.status = 'guest';
        vi.spyOn(authService, 'currentOwner').mockRejectedValue({
            isAxiosError: true,
            response: { status: 401 },
        });
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
        vi.mocked(authService.currentOwner).mockResolvedValue({
            id: 1,
            name: 'Project Owner',
            email: 'owner@example.com',
        });
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/sign-in');
        await router.isReady();

        expect(router.currentRoute.value.path).toBe('/app');
    });

    it('revalidates an authenticated owner and redirects when the server session has expired', async () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Project Owner', email: 'owner@example.com' };
        const router = createRouter({ history: createMemoryHistory(), routes });
        router.beforeEach(authenticationGuard);

        await router.push('/app');
        await router.isReady();

        expect(authService.currentOwner).toHaveBeenCalledOnce();
        expect(authStore.owner).toBeNull();
        expect(router.currentRoute.value.path).toBe('/sign-in');
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
