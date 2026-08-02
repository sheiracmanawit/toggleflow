export interface DashboardProjectSummary {
    id: number;
    name: string;
    slug: string;
    active_flag_count: number;
    production_enabled_count: number;
    updated_at: string;
}

export interface RecentActivity {
    id: number;
    action: string;
    project: { id: number; name: string };
    subject: { type: string; id: number; name: string };
    actor: { id: number; name: string } | null;
    environment: { key: string | null; name: string | null } | null;
    created_at: string;
}

export interface DashboardSummary {
    project_count: number;
    active_flag_count: number;
    production_enabled_count: number;
    projects: DashboardProjectSummary[];
    recent_activity: RecentActivity[];
}
