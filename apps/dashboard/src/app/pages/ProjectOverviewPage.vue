<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import { useProjectOverview } from '../composables/useProjectOverview';

const {
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
} = useProjectOverview();
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

            <section v-if="project.status === 'active'" class="mt-8" aria-labelledby="release-state-heading">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="release-state-heading" class="text-xl font-semibold">Release state</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Compare each active flag across Development, Staging, and Production.
                        </p>
                    </div>
                    <RouterLink
                        class="text-sm font-semibold text-brand hover:underline"
                        :to="`/projects/${project.id}/flags`"
                    >
                        Manage flags
                    </RouterLink>
                </div>
                <div v-if="flags.length === 0" class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold">No feature flags yet</h3>
                    <p class="mt-2 text-sm text-slate-600">Create a flag to begin comparing environment state.</p>
                </div>
                <ul v-else class="mt-4 grid gap-4 sm:hidden" aria-label="Mobile release state">
                    <li
                        v-for="row in releaseStateRows"
                        :key="row.flag.id"
                        class="rounded-2xl border border-slate-200 bg-white p-5"
                    >
                        <RouterLink
                            class="font-semibold text-brand hover:underline"
                            :to="`/projects/${project.id}/flags/${row.flag.id}`"
                        >
                            {{ row.flag.name }}
                        </RouterLink>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ row.flag.key }}</p>
                        <dl class="mt-4 grid gap-3">
                            <div
                                v-for="state in row.states"
                                :key="state.environment.id"
                                class="flex items-center justify-between gap-4"
                            >
                                <dt class="font-medium">{{ state.environment.name }}</dt>
                                <dd>
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 font-semibold"
                                        :class="state.classes"
                                    >
                                        <span aria-hidden="true">{{ state.symbol }}</span>
                                        {{ state.label }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </li>
                </ul>
                <div
                    v-if="flags.length > 0"
                    class="mt-4 hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white sm:block"
                >
                    <table class="min-w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold" scope="col">Flag</th>
                                <th
                                    v-for="environment in project.environments"
                                    :key="environment.id"
                                    class="px-4 py-3 font-semibold"
                                    scope="col"
                                >
                                    {{ environment.name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="row in releaseStateRows" :key="row.flag.id">
                                <th class="px-4 py-4 font-medium" scope="row">
                                    <RouterLink
                                        class="text-brand hover:underline"
                                        :to="`/projects/${project.id}/flags/${row.flag.id}`"
                                    >
                                        {{ row.flag.name }}
                                    </RouterLink>
                                    <span class="mt-1 block font-mono text-xs font-normal text-slate-500">
                                        {{ row.flag.key }}
                                    </span>
                                </th>
                                <td v-for="state in row.states" :key="state.environment.id" class="px-4 py-4">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 font-semibold"
                                        :class="state.classes"
                                    >
                                        <span aria-hidden="true">{{ state.symbol }}</span>
                                        {{ state.label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                v-if="project.status === 'active'"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-6"
                aria-labelledby="flags-heading"
            >
                <h2 id="flags-heading" class="text-xl font-semibold">Feature flags</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Create boolean flags and control Development, Staging, and Production independently.
                </p>
                <RouterLink
                    class="mt-4 inline-flex rounded-lg bg-brand px-4 py-2 font-semibold text-white"
                    :to="`/projects/${project.id}/flags`"
                >
                    Manage feature flags
                </RouterLink>
            </section>

            <section
                v-if="project.status === 'active'"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-6"
                aria-labelledby="api-keys-heading"
            >
                <h2 id="api-keys-heading" class="text-xl font-semibold">API keys</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Issue environment-scoped credentials for server-side flag evaluation.
                </p>
                <RouterLink
                    class="mt-4 inline-flex rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                    :to="`/projects/${project.id}/api-keys`"
                >
                    Manage API keys
                </RouterLink>
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
