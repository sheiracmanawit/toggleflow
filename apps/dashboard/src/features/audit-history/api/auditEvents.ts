import type { AuditEvent, AuditEventPage } from '../types/auditEvents';
import { dashboardHttp } from '@shared/api/http';

interface PaginatedResponse {
    data: AuditEvent[];
    meta: { current_page: number; last_page: number; total: number };
}

export const auditEventService = {
    async list(projectId: number, page = 1, signal?: AbortSignal): Promise<AuditEventPage> {
        const response = await dashboardHttp.get<PaginatedResponse>(`/dashboard/projects/${projectId}/audit-events`, {
            params: { page },
            signal,
        });

        return {
            events: response.data.data,
            currentPage: response.data.meta.current_page,
            lastPage: response.data.meta.last_page,
            total: response.data.meta.total,
        };
    },
};
