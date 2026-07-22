import axios from 'axios';

import type { Credentials, DataResponse, Owner } from '../types/auth';

export const dashboardHttp = axios.create({
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    withCredentials: true,
    withXSRFToken: true,
});

type SessionExpiredHandler = () => void;

let sessionExpiredHandler: SessionExpiredHandler | null = null;

export const setSessionExpiredHandler = (handler: SessionExpiredHandler): void => {
    sessionExpiredHandler = handler;
};

dashboardHttp.interceptors.response.use(
    (response) => response,
    (error: unknown) => {
        if (axios.isAxiosError(error) && [401, 419].includes(error.response?.status ?? 0)) {
            const method = error.config?.method?.toLowerCase();
            const isSessionBootstrap =
                error.config?.url === '/dashboard/auth/session' && ['get', 'post'].includes(method ?? '');

            if (!isSessionBootstrap) {
                sessionExpiredHandler?.();
            }
        }

        return Promise.reject(error);
    },
);

export const authService = {
    async csrf(): Promise<void> {
        await dashboardHttp.get('/sanctum/csrf-cookie');
    },

    async login(credentials: Credentials): Promise<Owner> {
        const response = await dashboardHttp.post<DataResponse<Owner>>('/dashboard/auth/session', credentials);
        return response.data.data;
    },

    async currentOwner(): Promise<Owner> {
        const response = await dashboardHttp.get<DataResponse<Owner>>('/dashboard/auth/session');
        return response.data.data;
    },

    async logout(): Promise<void> {
        await dashboardHttp.delete('/dashboard/auth/session');
    },
};
