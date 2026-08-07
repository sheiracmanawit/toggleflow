import type { AxiosRequestConfig } from 'axios';
import { describe, expect, it, vi } from 'vitest';

import { dashboardHttp, setSessionExpiredHandler } from './http';

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

describe('dashboard HTTP transport', () => {
    it.each([401, 419])('notifies the app when an established session expires with %s', async (status) => {
        const handler = vi.fn();
        setSessionExpiredHandler(handler);

        await expect(rejectWith(status, { method: 'get', url: '/dashboard/projects' })).rejects.toMatchObject({
            response: { status },
        });

        expect(handler).toHaveBeenCalledOnce();
    });

    it('does not report an unauthenticated session bootstrap as expiry', async () => {
        const handler = vi.fn();
        setSessionExpiredHandler(handler);

        await expect(rejectWith(401, { method: 'get', url: '/dashboard/auth/session' })).rejects.toMatchObject({
            response: { status: 401 },
        });
        expect(handler).not.toHaveBeenCalled();
    });
});
