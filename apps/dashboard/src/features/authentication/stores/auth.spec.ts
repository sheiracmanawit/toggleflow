import { createPinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

import { useAuthStore } from './auth';

describe('auth store', () => {
    const pinia = createPinia();
    const authStore = useAuthStore(pinia);

    beforeEach(() => authStore.resetForTests());

    it('preserves the first expired-session message when duplicate failures arrive', () => {
        authStore.status = 'authenticated';
        authStore.owner = { id: 1, name: 'Release Owner', email: 'owner@example.test' };

        authStore.expire();

        expect(authStore.status).toBe('guest');
        expect(authStore.consumeMessage()).toBe('Your session has expired. Please sign in again.');

        authStore.expire();

        expect(authStore.message).toBe('');
    });
});
