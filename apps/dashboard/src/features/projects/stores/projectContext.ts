import axios from 'axios';
import { defineStore } from 'pinia';
import { ref } from 'vue';

import { projectService } from '@features/projects';
import type { ProjectSummary } from '@features/projects';

export const useProjectContextStore = defineStore('project-context', () => {
    const projects = ref<ProjectSummary[]>([]);
    const isLoading = ref(false);
    const error = ref('');
    let controller: AbortController | null = null;

    const load = async (authenticated: boolean): Promise<void> => {
        controller?.abort();
        projects.value = [];
        error.value = '';

        if (!authenticated) {
            isLoading.value = false;
            return;
        }

        const requestController = new AbortController();
        controller = requestController;
        isLoading.value = true;

        try {
            const loadedProjects = await projectService.list(requestController.signal);
            if (controller === requestController) {
                projects.value = loadedProjects;
            }
        } catch (loadError: unknown) {
            if (controller === requestController && !axios.isCancel(loadError)) {
                error.value = 'Project navigation is unavailable.';
            }
        } finally {
            if (controller === requestController) {
                isLoading.value = false;
            }
        }
    };

    const updateProject = (project: ProjectSummary): void => {
        const index = projects.value.findIndex((candidate) => candidate.id === project.id);

        if (project.status === 'archived') {
            if (index >= 0) projects.value.splice(index, 1);
            return;
        }

        if (index >= 0) {
            projects.value[index] = project;
        } else {
            projects.value.push(project);
        }
    };

    const cancel = (): void => controller?.abort();

    const resetForTests = (): void => {
        controller?.abort();
        controller = null;
        projects.value = [];
        isLoading.value = false;
        error.value = '';
    };

    return { projects, isLoading, error, load, updateProject, cancel, resetForTests };
});
