import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

import { projectService, type Environment, type Project, type ValidationErrors } from '@features/projects';
import { ApiKeyValidationError, apiKeyService } from '../api/apiKeys';
import type { ApiKey } from '../types/apiKeys';

export const useApiKeyLifecycle = () => {
    const route = useRoute();
    const project = ref<Project | null>(null);
    const apiKeys = ref<ApiKey[]>([]);
    const isLoading = ref(true);
    const loadError = ref('');
    const showCreate = ref(false);
    const isSubmitting = ref(false);
    const mutationError = ref('');
    const successMessage = ref('');
    const validationErrors = ref<ValidationErrors>({});
    const form = reactive({ name: '', environmentId: 0 });
    const issuedCredential = ref('');
    const issuedKey = ref<ApiKey | null>(null);
    const acknowledged = ref(false);
    const copyMessage = ref('');
    const keyToRevoke = ref<ApiKey | null>(null);
    const isRevoking = ref(false);
    let controller: AbortController | null = null;

    const keysFor = (environment: Environment): ApiKey[] =>
        apiKeys.value.filter((apiKey) => apiKey.environment.id === environment.id);
    const environmentExample = computed(() =>
        issuedCredential.value ? `TOGGLEFLOW_API_KEY=${issuedCredential.value}` : '',
    );

    const clearSecret = (): void => {
        issuedCredential.value = '';
        issuedKey.value = null;
        acknowledged.value = false;
        copyMessage.value = '';
    };
    const resetProjectInteractionState = (): void => {
        clearSecret();
        showCreate.value = false;
        isSubmitting.value = false;
        mutationError.value = '';
        successMessage.value = '';
        validationErrors.value = {};
        form.name = '';
        keyToRevoke.value = null;
        isRevoking.value = false;
    };

    const load = async (): Promise<void> => {
        controller?.abort();
        controller = new AbortController();
        const projectId = Number(route.params.projectId);
        resetProjectInteractionState();
        isLoading.value = true;
        loadError.value = '';
        project.value = null;
        apiKeys.value = [];
        try {
            const [loadedProject, loadedKeys] = await Promise.all([
                projectService.get(projectId, controller.signal),
                apiKeyService.list(projectId, controller.signal),
            ]);
            if (Number(route.params.projectId) === projectId) {
                project.value = loadedProject;
                apiKeys.value = loadedKeys;
                form.environmentId = loadedProject.environments[0]?.id ?? 0;
            }
        } catch (error: unknown) {
            if (!axios.isCancel(error)) loadError.value = 'API keys could not be loaded for this project.';
        } finally {
            if (Number(route.params.projectId) === projectId) isLoading.value = false;
        }
    };

    const issue = async (): Promise<void> => {
        if (isSubmitting.value || !project.value) return;
        const projectId = project.value.id;
        isSubmitting.value = true;
        mutationError.value = '';
        validationErrors.value = {};
        try {
            const issued = await apiKeyService.issue(projectId, form.environmentId, form.name);
            if (Number(route.params.projectId) !== projectId) return;
            apiKeys.value = [issued.apiKey, ...apiKeys.value];
            issuedKey.value = issued.apiKey;
            issuedCredential.value = issued.credential;
            showCreate.value = false;
            form.name = '';
        } catch (error: unknown) {
            if (Number(route.params.projectId) !== projectId) return;
            if (error instanceof ApiKeyValidationError) validationErrors.value = error.errors;
            else mutationError.value = 'The API key was not issued. Existing credentials are unchanged.';
        } finally {
            if (Number(route.params.projectId) === projectId) isSubmitting.value = false;
        }
    };

    const copyCredential = async (): Promise<void> => {
        try {
            await window.navigator.clipboard.writeText(issuedCredential.value);
            copyMessage.value = 'API key copied.';
        } catch {
            copyMessage.value = 'Copy failed. Select the visible API key and copy it manually.';
        }
    };
    const dismissIssued = (): void => {
        if (acknowledged.value) {
            clearSecret();
            successMessage.value = 'API key issued. Store it securely; ToggleFlow cannot display it again.';
        }
    };
    const revoke = async (): Promise<void> => {
        if (!project.value || !keyToRevoke.value || isRevoking.value) return;
        const projectId = project.value.id;
        const apiKeyId = keyToRevoke.value.id;
        isRevoking.value = true;
        mutationError.value = '';
        try {
            const updated = await apiKeyService.revoke(projectId, apiKeyId);
            if (Number(route.params.projectId) !== projectId) return;
            apiKeys.value = apiKeys.value.map((apiKey) => (apiKey.id === updated.id ? updated : apiKey));
            keyToRevoke.value = null;
            successMessage.value = 'API key revoked.';
        } catch {
            if (Number(route.params.projectId) !== projectId) return;
            mutationError.value = 'The API key was not revoked. Its last confirmed state is still shown.';
        } finally {
            if (Number(route.params.projectId) === projectId) isRevoking.value = false;
        }
    };

    watch(() => route.params.projectId, load, { immediate: true });
    onBeforeUnmount(() => {
        controller?.abort();
        clearSecret();
    });

    return {
        route,
        project,
        apiKeys,
        isLoading,
        loadError,
        showCreate,
        isSubmitting,
        mutationError,
        successMessage,
        validationErrors,
        form,
        issuedCredential,
        issuedKey,
        acknowledged,
        copyMessage,
        keyToRevoke,
        isRevoking,
        keysFor,
        environmentExample,
        load,
        issue,
        copyCredential,
        dismissIssued,
        revoke,
    };
};
