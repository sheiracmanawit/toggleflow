import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { projectService, type Project, type ValidationErrors } from '@features/projects';
import { FeatureFlagValidationError, featureFlagService } from '../api/featureFlags';
import type { EnvironmentFlagState, FeatureFlag } from '../types/featureFlags';

export const useFeatureFlagList = () => {
    const route = useRoute();
    const router = useRouter();
    const project = ref<Project | null>(null);
    const flags = ref<FeatureFlag[]>([]);
    const isLoading = ref(true);
    const loadError = ref('');
    const showCreateForm = ref(false);
    const isSubmitting = ref(false);
    const submitError = ref('');
    const validationErrors = ref<ValidationErrors>({});
    const form = reactive({ name: '', key: '', description: '' });
    const keyWasEdited = ref(false);
    let controller: AbortController | null = null;

    const stateFor = (flag: FeatureFlag, environmentId: number): EnvironmentFlagState | undefined =>
        flag.environment_states.find((state) => state.environment.id === environmentId);
    const suggestedKey = computed(() =>
        form.name
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, ''),
    );

    const load = async (): Promise<void> => {
        controller?.abort();
        controller = new AbortController();
        const projectId = Number(route.params.projectId);
        isLoading.value = true;
        loadError.value = '';
        try {
            const [loadedProject, loadedFlags] = await Promise.all([
                projectService.get(projectId, controller.signal),
                featureFlagService.list(projectId, controller.signal),
            ]);
            if (Number(route.params.projectId) === projectId) {
                project.value = loadedProject;
                flags.value = loadedFlags;
            }
        } catch (error: unknown) {
            if (!axios.isCancel(error)) {
                loadError.value = 'Feature flags could not be loaded or you do not have access to this project.';
            }
        } finally {
            if (Number(route.params.projectId) === projectId) isLoading.value = false;
        }
    };

    const openCreate = (): void => {
        showCreateForm.value = true;
        submitError.value = '';
        validationErrors.value = {};
    };

    const submit = async (): Promise<void> => {
        if (isSubmitting.value) return;
        const projectId = Number(route.params.projectId);
        isSubmitting.value = true;
        submitError.value = '';
        validationErrors.value = {};
        try {
            const created = await featureFlagService.create(projectId, form);
            if (Number(route.params.projectId) === projectId)
                await router.push(`/projects/${projectId}/flags/${created.id}`);
        } catch (error: unknown) {
            if (error instanceof FeatureFlagValidationError) validationErrors.value = error.errors;
            else submitError.value = 'The feature flag was not created. Please try again.';
        } finally {
            isSubmitting.value = false;
        }
    };

    watch(suggestedKey, (value) => {
        if (!keyWasEdited.value) form.key = value;
    });
    watch(() => route.params.projectId, load, { immediate: true });
    onBeforeUnmount(() => controller?.abort());

    return {
        route,
        project,
        flags,
        isLoading,
        loadError,
        showCreateForm,
        isSubmitting,
        submitError,
        validationErrors,
        form,
        keyWasEdited,
        stateFor,
        load,
        openCreate,
        submit,
    };
};
