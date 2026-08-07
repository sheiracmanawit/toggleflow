import axios from 'axios';

import type { ApiKey, ApiKeyValidationErrors, IssuedApiKey } from '../types/apiKeys';
import { dashboardHttp } from '@shared/api/http';

interface DataResponse<T> {
    data: T;
}

interface IssuedResponse extends DataResponse<ApiKey> {
    credential: string;
}

export class ApiKeyValidationError extends Error {
    constructor(public readonly errors: ApiKeyValidationErrors) {
        super('API key validation failed.');
    }
}

const basePath = (projectId: number): string => `/dashboard/projects/${projectId}/api-keys`;

export const apiKeyService = {
    async list(projectId: number, signal?: AbortSignal): Promise<ApiKey[]> {
        const response = await dashboardHttp.get<DataResponse<ApiKey[]>>(basePath(projectId), { signal });
        return response.data.data;
    },

    async issue(projectId: number, environmentId: number, name: string): Promise<IssuedApiKey> {
        try {
            const response = await dashboardHttp.post<IssuedResponse>(
                `/dashboard/projects/${projectId}/environments/${environmentId}/api-keys`,
                { name },
            );
            return { apiKey: response.data.data, credential: response.data.credential };
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                throw new ApiKeyValidationError(error.response.data.errors as ApiKeyValidationErrors);
            }
            throw error;
        }
    },

    async revoke(projectId: number, apiKeyId: number): Promise<ApiKey> {
        const response = await dashboardHttp.post<DataResponse<ApiKey>>(`${basePath(projectId)}/${apiKeyId}/revoke`);
        return response.data.data;
    },
};
