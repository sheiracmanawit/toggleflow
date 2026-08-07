export interface DashboardProjectSummary {
    id: number;
    name: string;
    slug: string;
    active_flag_count: number;
    production_enabled_count: number;
    updated_at: string;
}

export type RecentActivity = AuditEvent;

export interface DashboardSummary {
    project_count: number;
    active_flag_count: number;
    production_enabled_count: number;
    projects: DashboardProjectSummary[];
    recent_activity: RecentActivity[];
}
import type { AuditEvent } from '@features/audit-history';
