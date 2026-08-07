import type { DashboardSummary } from '../types/dashboard';
import { dashboardHttp } from '@shared/api/http';

interface DataResponse<T> {
    data: T;
}

export const dashboardService = {
    async getSummary(signal?: AbortSignal): Promise<DashboardSummary> {
        const response = await dashboardHttp.get<DataResponse<DashboardSummary>>('/dashboard/summary', { signal });
        return response.data.data;
    },
};
