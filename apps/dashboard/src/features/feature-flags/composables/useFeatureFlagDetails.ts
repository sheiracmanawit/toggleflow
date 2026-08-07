import axios from 'axios';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import type { ValidationErrors } from '@features/projects';
import { FeatureFlagValidationError, featureFlagService } from '../api/featureFlags';
import type { EnvironmentFlagState, FeatureFlag } from '../types/featureFlags';

export const useFeatureFlagDetails = () => {
    const route = useRoute();
    const router = useRouter();
    const flag = ref<FeatureFlag | null>(null);
    const isLoading = ref(true);
    const loadError = ref('');
    const isEditing = ref(false);
    const isSaving = ref(false);
    const saveError = ref('');
    const successMessage = ref('');
    const validationErrors = ref<ValidationErrors>({});
    const form = reactive({ name: '', description: '' });
    const pendingEnvironmentId = ref<number | null>(null);
    const stateError = ref('');
    const confirmation = ref<{ state: EnvironmentFlagState; enabled: boolean } | null>(null);
    const showArchiveDialog = ref(false);
    const isArchiving = ref(false);
    const archiveError = ref('');
    let controller: AbortController | null = null;

    const ids = (): { projectId: number; flagId: number } => ({
        projectId: Number(route.params.projectId),
        flagId: Number(route.params.flagId),
    });
    const isCurrent = (requested: { projectId: number; flagId: number }): boolean => {
        const current = ids();
        return current.projectId === requested.projectId && current.flagId === requested.flagId;
    };

    const resetWorkflowState = (): void => {
        isEditing.value = false;
        isSaving.value = false;
        saveError.value = '';
        successMessage.value = '';
        validationErrors.value = {};
        form.name = '';
        form.description = '';
        pendingEnvironmentId.value = null;
        stateError.value = '';
        confirmation.value = null;
        showArchiveDialog.value = false;
        isArchiving.value = false;
        archiveError.value = '';
    };

    const load = async (): Promise<void> => {
        controller?.abort();
        controller = new AbortController();
        const requested = ids();
        resetWorkflowState();
        isLoading.value = true;
        loadError.value = '';
        flag.value = null;
        try {
            const loaded = await featureFlagService.get(requested.projectId, requested.flagId, controller.signal);
            if (isCurrent(requested)) flag.value = loaded;
        } catch (error: unknown) {
            if (!axios.isCancel(error) && isCurrent(requested))
                loadError.value = 'This feature flag could not be found or you do not have access to it.';
        } finally {
            if (isCurrent(requested)) isLoading.value = false;
        }
    };

    const startEditing = (): void => {
        if (!flag.value) return;
        form.name = flag.value.name;
        form.description = flag.value.description ?? '';
        validationErrors.value = {};
        saveError.value = '';
        isEditing.value = true;
    };

    const save = async (): Promise<void> => {
        if (!flag.value || isSaving.value) return;
        const requested = ids();
        isSaving.value = true;
        validationErrors.value = {};
        saveError.value = '';
        try {
            const updated = await featureFlagService.update(requested.projectId, requested.flagId, form);
            if (!isCurrent(requested)) return;
            flag.value = updated;
            isEditing.value = false;
            successMessage.value = 'Flag details saved.';
        } catch (error: unknown) {
            if (!isCurrent(requested)) return;
            if (error instanceof FeatureFlagValidationError) validationErrors.value = error.errors;
            else saveError.value = 'Changes were not saved. The last confirmed flag information is still shown.';
        } finally {
            if (isCurrent(requested)) isSaving.value = false;
        }
    };

    const requestStateChange = (state: EnvironmentFlagState): void => {
        const enabled = !state.enabled;
        stateError.value = '';
        if (state.environment.key === 'production') confirmation.value = { state, enabled };
        else void changeState(state, enabled);
    };

    const changeState = async (state: EnvironmentFlagState, enabled: boolean): Promise<void> => {
        if (!flag.value || pendingEnvironmentId.value !== null) return;
        const requested = ids();
        pendingEnvironmentId.value = state.environment.id;
        stateError.value = '';
        try {
            const updated = await featureFlagService.setState(
                requested.projectId,
                requested.flagId,
                state.environment.id,
                enabled,
            );
            if (!isCurrent(requested)) return;
            flag.value = updated;
            successMessage.value = `${state.environment.name} is now ${enabled ? 'enabled' : 'disabled'}.`;
            confirmation.value = null;
        } catch {
            if (!isCurrent(requested)) return;
            stateError.value = `${state.environment.name} was not changed. The last confirmed state is still shown.`;
        } finally {
            if (isCurrent(requested)) pendingEnvironmentId.value = null;
        }
    };

    const archive = async (): Promise<void> => {
        if (!flag.value || isArchiving.value) return;
        const requested = ids();
        isArchiving.value = true;
        archiveError.value = '';
        try {
            await featureFlagService.archive(requested.projectId, requested.flagId);
            if (isCurrent(requested)) await router.replace(`/projects/${requested.projectId}/flags`);
        } catch {
            if (isCurrent(requested)) archiveError.value = 'The flag was not archived. It remains active.';
        } finally {
            if (isCurrent(requested)) isArchiving.value = false;
        }
    };

    watch(() => [route.params.projectId, route.params.flagId], load, { immediate: true });
    onBeforeUnmount(() => controller?.abort());

    return {
        route,
        flag,
        isLoading,
        loadError,
        isEditing,
        isSaving,
        saveError,
        successMessage,
        validationErrors,
        form,
        pendingEnvironmentId,
        stateError,
        confirmation,
        showArchiveDialog,
        isArchiving,
        archiveError,
        load,
        startEditing,
        save,
        requestStateChange,
        changeState,
        archive,
    };
};
