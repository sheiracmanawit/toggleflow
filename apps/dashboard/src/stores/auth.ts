import axios from 'axios';
import { reactive } from 'vue';

import { authService } from '../services/auth';
import type { Credentials, Owner } from '../types/auth';

type AuthStatus = 'unknown' | 'authenticated' | 'guest';

const state = reactive<{
    status: AuthStatus;
    owner: Owner | null;
    message: string;
}>({
    status: 'unknown',
    owner: null,
    message: '',
});

let resolving: Promise<void> | null = null;

const resolve = async (): Promise<void> => {
    if (state.status !== 'unknown') {
        return;
    }

    resolving ??= authService
        .currentOwner()
        .then((owner) => {
            state.owner = owner;
            state.status = 'authenticated';
        })
        .catch((error: unknown) => {
            state.owner = null;
            state.status = 'guest';

            if (!axios.isAxiosError(error) || error.response?.status !== 401) {
                state.message = 'ToggleFlow could not verify your session. Please sign in again.';
            }
        })
        .finally(() => {
            resolving = null;
        });

    await resolving;
};

const login = async (credentials: Credentials): Promise<void> => {
    await authService.csrf();
    state.owner = await authService.login(credentials);
    state.status = 'authenticated';
    state.message = '';
};

const logout = async (): Promise<void> => {
    await authService.logout();
    clear();
};

const clear = (): void => {
    state.owner = null;
    state.status = 'guest';
};

const resetForTests = (): void => {
    state.owner = null;
    state.status = 'unknown';
    state.message = '';
    resolving = null;
};

export const authStore = {
    state,
    get owner(): Owner | null {
        return state.owner;
    },
    get isAuthenticated(): boolean {
        return state.status === 'authenticated';
    },
    resolve,
    login,
    logout,
    clear,
    setMessage(message: string): void {
        state.message = message;
    },
    consumeMessage(): string {
        const message = state.message;
        state.message = '';
        return message;
    },
    resetForTests,
};
