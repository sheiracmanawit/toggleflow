import axios from 'axios';

import type {
    CreateFeatureFlagInput,
    FeatureFlag,
    FeatureFlagValidationErrors,
    UpdateFeatureFlagInput,
} from '../types/featureFlags';
import { dashboardHttp } from '@shared/api/http';

interface DataResponse<T> {
    data: T;
}

export class FeatureFlagValidationError extends Error {
    constructor(public readonly errors: FeatureFlagValidationErrors) {
        super('Feature flag validation failed.');
    }
}

const translateError = (error: unknown): never => {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
        const errors = error.response.data?.errors;
        if (typeof errors === 'object' && errors !== null) {
            throw new FeatureFlagValidationError(errors as FeatureFlagValidationErrors);
        }
    }
    throw error;
};

const basePath = (projectId: number): string => `/dashboard/projects/${projectId}/flags`;

export const featureFlagService = {
    async list(projectId: number, signal?: AbortSignal): Promise<FeatureFlag[]> {
        const response = await dashboardHttp.get<DataResponse<FeatureFlag[]>>(basePath(projectId), { signal });
        return response.data.data;
    },

    async create(projectId: number, input: CreateFeatureFlagInput): Promise<FeatureFlag> {
        try {
            const response = await dashboardHttp.post<DataResponse<FeatureFlag>>(basePath(projectId), input);
            return response.data.data;
        } catch (error: unknown) {
            return translateError(error);
        }
    },

    async get(projectId: number, flagId: number, signal?: AbortSignal): Promise<FeatureFlag> {
        const response = await dashboardHttp.get<DataResponse<FeatureFlag>>(`${basePath(projectId)}/${flagId}`, {
            signal,
        });
        return response.data.data;
    },

    async update(projectId: number, flagId: number, input: UpdateFeatureFlagInput): Promise<FeatureFlag> {
        try {
            const response = await dashboardHttp.patch<DataResponse<FeatureFlag>>(
                `${basePath(projectId)}/${flagId}`,
                input,
            );
            return response.data.data;
        } catch (error: unknown) {
            return translateError(error);
        }
    },

    async setState(projectId: number, flagId: number, environmentId: number, enabled: boolean): Promise<FeatureFlag> {
        const response = await dashboardHttp.put<DataResponse<FeatureFlag>>(
            `${basePath(projectId)}/${flagId}/environments/${environmentId}`,
            { enabled },
        );
        return response.data.data;
    },

    async archive(projectId: number, flagId: number): Promise<FeatureFlag> {
        const response = await dashboardHttp.post<DataResponse<FeatureFlag>>(
            `${basePath(projectId)}/${flagId}/archive`,
        );
        return response.data.data;
    },
};
