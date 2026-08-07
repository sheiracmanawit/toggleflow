import axios from 'axios';

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
