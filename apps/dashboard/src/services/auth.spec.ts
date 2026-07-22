import type { AxiosRequestConfig } from 'axios';
import { describe, expect, it, vi } from 'vitest';

import { dashboardHttp, setSessionExpiredHandler } from './auth';

const rejectWith = async (status: number, config: AxiosRequestConfig): Promise<void> => {
    await dashboardHttp.request({
        ...config,
        adapter: (requestConfig) =>
            Promise.reject({
                isAxiosError: true,
                config: requestConfig,
                response: { status },
            }),
    });
};

describe('dashboard authentication client', () => {
    it.each([401, 419])('reports a %s from a protected action as session expiry', async (status) => {
        const onSessionExpired = vi.fn();
        setSessionExpiredHandler(onSessionExpired);

        await expect(rejectWith(status, { method: 'delete', url: '/dashboard/auth/session' })).rejects.toMatchObject({
            response: { status },
        });

        expect(onSessionExpired).toHaveBeenCalledOnce();
    });

    it('leaves invalid login responses with the sign-in form', async () => {
        const onSessionExpired = vi.fn();
        setSessionExpiredHandler(onSessionExpired);

        await expect(rejectWith(401, { method: 'post', url: '/dashboard/auth/session' })).rejects.toMatchObject({
            response: { status: 401 },
        });

        expect(onSessionExpired).not.toHaveBeenCalled();
    });
});
