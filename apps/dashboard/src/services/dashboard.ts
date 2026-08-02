import type { DashboardSummary } from '../types';
import { dashboardHttp } from './auth';

interface DataResponse<T> {
    data: T;
}

export const dashboardService = {
    async getSummary(signal?: AbortSignal): Promise<DashboardSummary> {
        const response = await dashboardHttp.get<DataResponse<DashboardSummary>>('/dashboard/summary', { signal });
        return response.data.data;
    },
};
