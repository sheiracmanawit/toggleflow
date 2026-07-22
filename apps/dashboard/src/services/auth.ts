import axios from 'axios';

import type { Credentials, DataResponse, Owner } from '../types/auth';

export const dashboardHttp = axios.create({
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    withCredentials: true,
    withXSRFToken: true,
});

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
