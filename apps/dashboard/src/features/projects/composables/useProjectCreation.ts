import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { ProjectValidationError, projectService } from '../api/projects';
import type { ProjectSummary, ValidationErrors } from '../types/projects';

export const useProjectCreation = () => {
    const router = useRouter();
    const projects = ref<ProjectSummary[]>([]);
    const isLoading = ref(true);
    const loadError = ref('');
    const showCreateForm = ref(false);
    const isSubmitting = ref(false);
    const submitError = ref('');
    const validationErrors = ref<ValidationErrors>({});
    const form = reactive({ name: '', slug: '', description: '' });

    const suggestedSlug = computed(() =>
        form.name
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, ''),
    );

    const load = async (): Promise<void> => {
        isLoading.value = true;
        loadError.value = '';
        try {
            projects.value = await projectService.list();
        } catch {
            loadError.value = 'ToggleFlow could not load your projects. Try again.';
        } finally {
            isLoading.value = false;
        }
    };

    const openCreate = (): void => {
        showCreateForm.value = true;
        submitError.value = '';
        validationErrors.value = {};
    };

    const submit = async (): Promise<void> => {
        if (isSubmitting.value) return;
        isSubmitting.value = true;
        submitError.value = '';
        validationErrors.value = {};
        try {
            const project = await projectService.create({
                name: form.name,
                slug: form.slug || suggestedSlug.value,
                description: form.description,
            });
            await router.push(`/projects/${project.id}`);
        } catch (error: unknown) {
            if (error instanceof ProjectValidationError) validationErrors.value = error.errors;
            else if (!axios.isCancel(error)) {
                submitError.value = 'The project was not created. Your entered information has been preserved.';
            }
        } finally {
            isSubmitting.value = false;
        }
    };

    onMounted(load);

    return {
        projects,
        isLoading,
        loadError,
        showCreateForm,
        isSubmitting,
        submitError,
        validationErrors,
        form,
        suggestedSlug,
        load,
        openCreate,
        submit,
    };
};
