export type ProjectStatus = 'active' | 'archived';

export interface Environment {
    id: number;
    name: string;
    key: 'development' | 'staging' | 'production';
    color: string;
}

export interface ProjectSummary {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: ProjectStatus;
    updated_at: string;
}

export interface Project extends ProjectSummary {
    environments: Environment[];
}

export interface ProjectInput {
    name: string;
    description: string;
}

export interface CreateProjectInput extends ProjectInput {
    slug: string;
}

export type ValidationErrors = Record<string, string[]>;
