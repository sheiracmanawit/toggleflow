import type { Environment, ValidationErrors } from '@features/projects';

export type FeatureFlagStatus = 'active' | 'archived';

export interface EnvironmentFlagState {
    environment: Environment;
    enabled: boolean;
    updated_at: string;
}

export interface FeatureFlag {
    id: number;
    project_id: number;
    name: string;
    key: string;
    description: string | null;
    status: FeatureFlagStatus;
    updated_at: string;
    environment_states: EnvironmentFlagState[];
}

export interface CreateFeatureFlagInput {
    name: string;
    key: string;
    description: string;
}

export interface UpdateFeatureFlagInput {
    name: string;
    description: string;
}

export type FeatureFlagValidationErrors = ValidationErrors;
