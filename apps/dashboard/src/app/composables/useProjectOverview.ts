import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { featureFlagService, type FeatureFlag } from '@features/feature-flags';
import {
    ProjectValidationError,
    projectService,
    useProjectContextStore,
    type Project,
    type ValidationErrors,
} from '@features/projects';
import { pinia } from '@app/pinia';

export const useProjectOverview = () => {
    const route = useRoute();
    const router = useRouter();
    const projectContextStore = useProjectContextStore(pinia);
    const project = ref<Project | null>(null);
    const flags = ref<FeatureFlag[]>([]);
    const isLoading = ref(true);
    const loadError = ref('');
    const isEditing = ref(false);
    const isSaving = ref(false);
    const saveError = ref('');
    const successMessage = ref('');
    const validationErrors = ref<ValidationErrors>({});
    const showArchiveDialog = ref(false);
    const isArchiving = ref(false);
    const archiveError = ref('');
    const form = reactive({ name: '', description: '' });
    let loadController: AbortController | null = null;

    const releaseStateRows = computed(() =>
        flags.value.map((flag) => ({
            flag,
            states: (project.value?.environments ?? []).map((environment) => {
                const state = flag.environment_states.find((candidate) => candidate.environment.id === environment.id);
                if (state === undefined) {
                    return { environment, label: 'Not configured', symbol: '?', classes: 'bg-amber-50 text-warning' };
                }
                return state.enabled
                    ? { environment, label: 'Enabled', symbol: '●', classes: 'bg-emerald-50 text-enabled' }
                    : { environment, label: 'Disabled', symbol: '○', classes: 'bg-slate-100 text-disabled' };
            }),
        })),
    );

    const resetProjectInteractionState = (): void => {
        isEditing.value = false;
        isSaving.value = false;
        saveError.value = '';
        successMessage.value = '';
        validationErrors.value = {};
        form.name = '';
        form.description = '';
        showArchiveDialog.value = false;
        isArchiving.value = false;
        archiveError.value = '';
    };

    const load = async (): Promise<void> => {
        loadController?.abort();
        loadController = new AbortController();
        const requestedId = Number(route.params.projectId);
        resetProjectInteractionState();
        isLoading.value = true;
        loadError.value = '';
        project.value = null;
        flags.value = [];
        try {
            const loaded = await projectService.get(requestedId, loadController.signal);
            const loadedFlags =
                loaded.status === 'active' ? await featureFlagService.list(requestedId, loadController.signal) : [];
            if (Number(route.params.projectId) === requestedId) {
                project.value = loaded;
                flags.value = loadedFlags;
                projectContextStore.updateProject(loaded);
            }
        } catch (error: unknown) {
            if (!axios.isCancel(error)) {
                loadError.value = 'This project could not be found or you do not have access to it.';
            }
        } finally {
            if (Number(route.params.projectId) === requestedId) isLoading.value = false;
        }
    };

    const startEditing = (): void => {
        if (!project.value) return;
        form.name = project.value.name;
        form.description = project.value.description ?? '';
        validationErrors.value = {};
        saveError.value = '';
        successMessage.value = '';
        isEditing.value = true;
    };

    const save = async (): Promise<void> => {
        if (!project.value || isSaving.value) return;
        const projectId = project.value.id;
        const input = { name: form.name, description: form.description };
        isSaving.value = true;
        saveError.value = '';
        validationErrors.value = {};
        try {
            const updatedProject = await projectService.update(projectId, input);
            if (Number(route.params.projectId) !== projectId) return;
            project.value = updatedProject;
            projectContextStore.updateProject(updatedProject);
            isEditing.value = false;
            successMessage.value = 'Project changes saved.';
        } catch (error: unknown) {
            if (Number(route.params.projectId) !== projectId) return;
            if (error instanceof ProjectValidationError) validationErrors.value = error.errors;
            else saveError.value = 'Changes were not saved. The last confirmed project information is still shown.';
        } finally {
            if (Number(route.params.projectId) === projectId) isSaving.value = false;
        }
    };

    const closeArchiveDialog = (): void => {
        if (!isArchiving.value) showArchiveDialog.value = false;
    };
    const archive = async (): Promise<void> => {
        if (!project.value || isArchiving.value) return;
        const projectId = project.value.id;
        isArchiving.value = true;
        archiveError.value = '';
        try {
            const archivedProject = await projectService.archive(projectId);
            if (Number(route.params.projectId) !== projectId) return;
            showArchiveDialog.value = false;
            projectContextStore.updateProject(archivedProject);
            await router.replace('/projects');
        } catch {
            if (Number(route.params.projectId) !== projectId) return;
            archiveError.value = 'The project was not archived. It remains active.';
        } finally {
            if (Number(route.params.projectId) === projectId) isArchiving.value = false;
        }
    };

    watch(() => route.params.projectId, load, { immediate: true });
    onBeforeUnmount(() => loadController?.abort());

    return {
        project,
        flags,
        isLoading,
        loadError,
        isEditing,
        isSaving,
        saveError,
        successMessage,
        validationErrors,
        showArchiveDialog,
        isArchiving,
        archiveError,
        form,
        releaseStateRows,
        load,
        startEditing,
        save,
        closeArchiveDialog,
        archive,
    };
};
