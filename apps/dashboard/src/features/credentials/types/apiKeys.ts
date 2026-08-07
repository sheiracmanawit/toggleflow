import type { Environment, ValidationErrors } from '@features/projects';

export type ApiKeyState = 'active' | 'revoked';

export interface ApiKey {
    id: number;
    name: string;
    prefix: string;
    state: ApiKeyState;
    created_at: string;
    last_used_at: string | null;
    revoked_at: string | null;
    environment: Environment;
}

export interface IssuedApiKey {
    apiKey: ApiKey;
    credential: string;
}

export type ApiKeyValidationErrors = ValidationErrors;
