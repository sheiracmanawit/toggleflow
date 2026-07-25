<script setup lang="ts">
import axios from 'axios';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import { AppDialog } from '../components/ui';
import { ProjectValidationError, projectService } from '../services';
import type { Project, ValidationErrors } from '../types';

const route = useRoute();
const router = useRouter();
const project = ref<Project | null>(null);
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

    try {
        const loaded = await projectService.get(requestedId, loadController.signal);

        if (Number(route.params.projectId) === requestedId) {
            project.value = loaded;
        }
    } catch (error: unknown) {
        if (!axios.isCancel(error)) {
            loadError.value = 'This project could not be found or you do not have access to it.';
        }
    } finally {
        if (Number(route.params.projectId) === requestedId) {
            isLoading.value = false;
        }
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
        isEditing.value = false;
        successMessage.value = 'Project changes saved.';
    } catch (error: unknown) {
        if (Number(route.params.projectId) !== projectId) return;

        if (error instanceof ProjectValidationError) {
            validationErrors.value = error.errors;
        } else {
            saveError.value = 'Changes were not saved. The last confirmed project information is still shown.';
        }
    } finally {
        if (Number(route.params.projectId) === projectId) {
            isSaving.value = false;
        }
    }
};

const closeArchiveDialog = (): void => {
    if (!isArchiving.value) {
        showArchiveDialog.value = false;
    }
};

const archive = async (): Promise<void> => {
    if (!project.value || isArchiving.value) return;
    const projectId = project.value.id;
    isArchiving.value = true;
    archiveError.value = '';

    try {
        await projectService.archive(projectId);

        if (Number(route.params.projectId) !== projectId) return;

        showArchiveDialog.value = false;
        await router.replace('/projects');
    } catch {
        if (Number(route.params.projectId) !== projectId) return;

        archiveError.value = 'The project was not archived. It remains active.';
    } finally {
        if (Number(route.params.projectId) === projectId) {
            isArchiving.value = false;
        }
    }
};

watch(() => route.params.projectId, load, { immediate: true });
onBeforeUnmount(() => loadController?.abort());
</script>

<template>
    <section aria-labelledby="project-heading">
        <RouterLink class="text-sm font-semibold text-brand hover:underline" to="/projects">← All projects</RouterLink>
        <p v-if="isLoading" class="mt-8 rounded-xl border border-slate-200 bg-white p-6" role="status">
            Loading project…
        </p>
        <div v-else-if="loadError" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h1 id="project-heading" class="text-xl font-semibold">Project unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="font-mono text-sm text-slate-500">{{ project.slug }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 id="project-heading" class="text-3xl font-bold">{{ project.name }}</h1>
                        <span
                            v-if="project.status === 'archived'"
                            class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-700"
                        >
                            Archived
                        </span>
                    </div>
                    <p class="mt-2 max-w-2xl text-slate-600">
                        {{ project.description || 'No project description yet.' }}
                    </p>
                    <p v-if="project.status === 'archived'" class="mt-3 max-w-2xl text-sm text-slate-600">
                        This project is archived. Its environments and history remain available for reference, but the
                        project can no longer be edited.
                    </p>
                </div>
                <button
                    v-if="project.status === 'active'"
                    class="self-start rounded-lg border border-slate-300 bg-white px-4 py-2 font-semibold"
                    type="button"
                    @click="startEditing"
                >
                    Edit project
                </button>
            </div>
            <p v-if="successMessage" class="mt-4 text-sm font-medium text-enabled" role="status">
                {{ successMessage }}
            </p>

            <section class="mt-8" aria-labelledby="environments-heading">
                <div>
                    <h2 id="environments-heading" class="text-xl font-semibold">Environments</h2>
                    <p class="mt-1 text-sm text-slate-600">Each deployment context remains separate.</p>
                </div>
                <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                    <li
                        v-for="environment in project.environments"
                        :key="environment.id"
                        class="rounded-2xl border border-slate-200 bg-white p-5"
                    >
                        <span
                            class="inline-block h-3 w-3 rounded-full"
                            :style="{ backgroundColor: environment.color }"
                            aria-hidden="true"
                        />
                        <h3 class="mt-3 font-semibold">{{ environment.name }}</h3>
                        <p class="mt-1 font-mono text-sm text-slate-500">{{ environment.key }}</p>
                    </li>
                </ul>
            </section>

            <form
                v-if="project.status === 'active' && isEditing"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-6"
                novalidate
                @submit.prevent="save"
            >
                <h2 class="text-xl font-semibold">Edit project</h2>
                <div class="mt-5 grid gap-5">
                    <div>
                        <label class="block text-sm font-semibold" for="edit-project-name">Name</label>
                        <input
                            id="edit-project-name"
                            v-model="form.name"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            :aria-describedby="validationErrors.name ? 'edit-project-name-error' : undefined"
                            :aria-invalid="Boolean(validationErrors.name)"
                        />
                        <p v-if="validationErrors.name" id="edit-project-name-error" class="mt-1 text-sm text-danger">
                            {{ validationErrors.name[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold" for="edit-project-description">
                            Description (optional)
                        </label>
                        <textarea
                            id="edit-project-description"
                            v-model="form.description"
                            class="mt-1 min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2"
                            :aria-describedby="
                                validationErrors.description ? 'edit-project-description-error' : undefined
                            "
                            :aria-invalid="Boolean(validationErrors.description)"
                        />
                        <p
                            v-if="validationErrors.description"
                            id="edit-project-description-error"
                            class="mt-1 text-sm text-danger"
                        >
                            {{ validationErrors.description[0] }}
                        </p>
                    </div>
                    <p v-if="saveError" class="text-sm text-danger" role="alert">{{ saveError }}</p>
                    <div class="flex gap-3">
                        <button
                            class="rounded-lg bg-brand px-4 py-2 font-semibold text-white disabled:opacity-60"
                            type="submit"
                            :disabled="isSaving"
                        >
                            {{ isSaving ? 'Saving…' : 'Save changes' }}
                        </button>
                        <button
                            class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                            type="button"
                            :disabled="isSaving"
                            @click="isEditing = false"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </form>

            <section
                v-if="project.status === 'active'"
                class="mt-10 rounded-2xl border border-red-200 bg-red-50 p-6"
                aria-labelledby="archive-heading"
            >
                <h2 id="archive-heading" class="text-lg font-semibold text-danger">Archive project</h2>
                <p class="mt-2 text-sm text-slate-700">
                    Archiving removes this project from active project views while retaining its environments and
                    history.
                </p>
                <button
                    class="mt-4 rounded-lg bg-danger px-4 py-2 font-semibold text-white"
                    type="button"
                    @click="showArchiveDialog = true"
                >
                    Archive project
                </button>
            </section>
        </template>

        <AppDialog
            v-if="showArchiveDialog && project?.status === 'active'"
            :title="`Archive ${project.name}?`"
            description="This project will leave active project views. Its environments and audit history will be retained."
            @cancel="closeArchiveDialog"
        >
            <p v-if="archiveError" class="mb-4 text-sm text-danger" role="alert">{{ archiveError }}</p>
            <div class="flex flex-wrap justify-end gap-3">
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                    type="button"
                    :disabled="isArchiving"
                    @click="closeArchiveDialog"
                >
                    Keep project
                </button>
                <button
                    class="rounded-lg bg-danger px-4 py-2 font-semibold text-white disabled:opacity-60"
                    type="button"
                    :disabled="isArchiving"
                    @click="archive"
                >
                    {{ isArchiving ? 'Archiving…' : 'Archive project' }}
                </button>
            </div>
        </AppDialog>
    </section>
</template>
