import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

import { authService } from '../api/auth';
import type { Credentials, Owner } from '../types/auth';

type AuthStatus = 'unknown' | 'authenticated' | 'guest';

export const useAuthStore = defineStore('auth', () => {
    const status = ref<AuthStatus>('unknown');
    const owner = ref<Owner | null>(null);
    const message = ref('');
    let resolving: Promise<void> | null = null;

    const isAuthenticated = computed(() => status.value === 'authenticated');

    const resolve = async (force = false): Promise<void> => {
        if (!force && status.value !== 'unknown') {
            return;
        }

        resolving ??= authService
            .currentOwner()
            .then((currentOwner) => {
                owner.value = currentOwner;
                status.value = 'authenticated';
            })
            .catch((error: unknown) => {
                owner.value = null;
                status.value = 'guest';

                if (!axios.isAxiosError(error) || error.response?.status !== 401) {
                    message.value = 'ToggleFlow could not verify your session. Please sign in again.';
                }
            })
            .finally(() => {
                resolving = null;
            });

        await resolving;
    };

    const login = async (credentials: Credentials): Promise<void> => {
        await authService.csrf();
        owner.value = await authService.login(credentials);
        status.value = 'authenticated';
        message.value = '';
    };

    const clear = (): void => {
        owner.value = null;
        status.value = 'guest';
    };

    const expire = (): void => {
        if (status.value === 'guest') return;

        clear();
        message.value = 'Your session has expired. Please sign in again.';
    };

    const logout = async (): Promise<void> => {
        await authService.logout();
        clear();
    };

    const setMessage = (value: string): void => {
        message.value = value;
    };

    const consumeMessage = (): string => {
        const currentMessage = message.value;
        message.value = '';
        return currentMessage;
    };

    const resetForTests = (): void => {
        owner.value = null;
        status.value = 'unknown';
        message.value = '';
        resolving = null;
    };

    return {
        status,
        owner,
        message,
        isAuthenticated,
        resolve,
        login,
        logout,
        clear,
        expire,
        setMessage,
        consumeMessage,
        resetForTests,
    };
});
