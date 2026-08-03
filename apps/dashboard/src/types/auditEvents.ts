export interface AuditEvent {
    id: number;
    action: string;
    project: { id: number; name: string };
    subject: { type: string; id: number; name: string };
    actor: { id: number | null; name: string };
    environment: { id: number | null; key: string | null; name: string | null } | null;
    changes: {
        before: Record<string, boolean | string | null>;
        after: Record<string, boolean | string | null>;
    };
    created_at: string;
}

export interface AuditEventPage {
    events: AuditEvent[];
    currentPage: number;
    lastPage: number;
    total: number;
}
