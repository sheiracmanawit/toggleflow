import axios from 'axios';

import type { CreateProjectInput, Project, ProjectInput, ProjectSummary, ValidationErrors } from '../types/projects';
import { dashboardHttp } from '@shared/api/http';

interface DataResponse<T> {
    data: T;
}

export class ProjectValidationError extends Error {
    constructor(public readonly errors: ValidationErrors) {
        super('Project validation failed.');
    }
}

const translateError = (error: unknown): never => {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
        const errors = error.response.data?.errors;

        if (typeof errors === 'object' && errors !== null) {
            throw new ProjectValidationError(errors as ValidationErrors);
        }
    }

    throw error;
};

export const projectService = {
    async list(signal?: AbortSignal): Promise<ProjectSummary[]> {
        const response = await dashboardHttp.get<DataResponse<ProjectSummary[]>>('/dashboard/projects', { signal });
        return response.data.data;
    },

    async create(input: CreateProjectInput): Promise<Project> {
        try {
            const response = await dashboardHttp.post<DataResponse<Project>>('/dashboard/projects', input);
            return response.data.data;
        } catch (error: unknown) {
            return translateError(error);
        }
    },

    async get(projectId: number, signal?: AbortSignal): Promise<Project> {
        const response = await dashboardHttp.get<DataResponse<Project>>(`/dashboard/projects/${projectId}`, {
            signal,
        });
        return response.data.data;
    },

    async update(projectId: number, input: ProjectInput): Promise<Project> {
        try {
            const response = await dashboardHttp.patch<DataResponse<Project>>(
                `/dashboard/projects/${projectId}`,
                input,
            );
            return response.data.data;
        } catch (error: unknown) {
            return translateError(error);
        }
    },

    async archive(projectId: number): Promise<Project> {
        const response = await dashboardHttp.post<DataResponse<Project>>(`/dashboard/projects/${projectId}/archive`);
        return response.data.data;
    },
};
