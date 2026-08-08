<script setup lang="ts">
import { computed } from 'vue';
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

const productionEnabledCount = computed(
    () =>
        flags.value.filter((flag) =>
            flag.environment_states.some((state) => state.environment.key === 'production' && state.enabled),
        ).length,
);
</script>

<template>
    <section class="-mx-4 -my-6 sm:-mx-6 sm:-my-8" aria-labelledby="project-heading">
        <p v-if="isLoading" class="p-6" role="status">Loading project…</p>
        <div v-else-if="loadError" class="m-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <h1 id="project-heading" class="text-xl font-semibold">Project unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <UDashboardToolbar class="border-b border-border px-4 py-3 sm:px-6">
                <template #left>
                    <RouterLink class="text-sm font-medium text-text-muted hover:text-text" to="/projects"
                        >← All projects</RouterLink
                    >
                    <div class="flex min-w-0 items-center gap-2">
                        <h1 id="project-heading" class="truncate text-sm font-semibold">{{ project.name }}</h1>
                        <span class="hidden font-mono text-xs text-text-muted sm:inline">{{ project.slug }}</span>
                        <span
                            v-if="project.status === 'archived'"
                            class="rounded-md bg-surface-muted px-2 py-1 text-xs font-semibold"
                        >
                            Archived
                        </span>
                    </div>
                </template>
                <template #right>
                    <div v-if="project.status === 'active'" class="flex flex-wrap gap-2">
                        <UButton :to="`/projects/${project.id}/flags`" icon="i-lucide-toggle-right" size="sm"
                            >Manage feature flags</UButton
                        >
                        <UButton
                            :to="`/projects/${project.id}/api-keys`"
                            color="neutral"
                            icon="i-lucide-key-round"
                            size="sm"
                            variant="outline"
                            >Manage API keys</UButton
                        >
                        <UButton
                            aria-label="Edit project"
                            color="neutral"
                            icon="i-lucide-pencil"
                            size="sm"
                            type="button"
                            variant="ghost"
                            @click="startEditing"
                        >
                            <span class="sr-only">Edit project</span>
                        </UButton>
                    </div>
                </template>
            </UDashboardToolbar>
            <div class="border-b border-border px-4 py-4 sm:px-6">
                <p class="max-w-3xl text-sm text-text-muted">
                    {{ project.description || 'No project description yet.' }}
                </p>
                <p v-if="project.status === 'archived'" class="mt-2 max-w-3xl text-sm text-text-muted">
                    This project is archived. Its environments and history remain available for reference.
                </p>
            </div>
            <p
                v-if="successMessage"
                class="border-b border-border px-4 py-3 text-sm font-medium text-enabled sm:px-6"
                role="status"
            >
                {{ successMessage }}
            </p>

            <section class="border-b border-border" aria-labelledby="environments-heading">
                <div
                    class="grid divide-y divide-slate-200 sm:grid-cols-[minmax(13rem,0.7fr)_minmax(0,1.3fr)] sm:divide-x sm:divide-y-0"
                >
                    <div class="px-4 py-3 sm:px-6">
                        <h2 id="environments-heading" class="font-semibold">Release overview</h2>
                        <dl class="mt-2 flex gap-5 text-sm">
                            <div>
                                <dt class="text-slate-500">Flags</dt>
                                <dd class="font-semibold">{{ flags.length }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Production enabled</dt>
                                <dd class="font-semibold">{{ productionEnabledCount }}</dd>
                            </div>
                        </dl>
                    </div>
                    <ul
                        class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6"
                        aria-label="Project environments"
                    >
                        <li
                            v-for="environment in project.environments"
                            :key="environment.id"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="inline-block h-2.5 w-2.5 rounded-full"
                                :style="{ backgroundColor: environment.color }"
                                aria-hidden="true"
                            />
                            <span
                                ><strong class="text-sm">{{ environment.name }}</strong>
                                <span class="font-mono text-xs text-slate-500">{{ environment.key }}</span></span
                            >
                        </li>
                    </ul>
                </div>
            </section>

            <section v-if="project.status === 'active'" aria-labelledby="release-state-heading">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border px-4 py-3 sm:px-6">
                    <div>
                        <h2 id="release-state-heading" class="text-sm font-semibold">Release state</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Compare each active flag across Development, Staging, and Production.
                        </p>
                    </div>
                </div>
                <div v-if="flags.length === 0" class="p-6">
                    <h3 class="font-semibold">No feature flags yet</h3>
                    <p class="mt-2 text-sm text-slate-600">Create a flag to begin comparing environment state.</p>
                </div>
                <ul v-else class="divide-y divide-border sm:hidden" aria-label="Mobile release state">
                    <li v-for="row in releaseStateRows" :key="row.flag.id" class="p-4">
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
                <div v-if="flags.length > 0" class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full border-collapse text-left text-sm">
                        <thead class="border-b border-border bg-surface-muted/50">
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
                        <tbody class="divide-y divide-border">
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

            <AppDialog
                v-if="project.status === 'active' && isEditing"
                title="Edit project"
                description="Update the project name and description. The machine-readable slug remains unchanged."
                @cancel="!isSaving && (isEditing = false)"
            >
                <form class="grid gap-5" novalidate @submit.prevent="save">
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
                            class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand disabled:opacity-60"
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
                </form>
            </AppDialog>

            <section
                v-if="project.status === 'active'"
                class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                aria-labelledby="archive-heading"
            >
                <div>
                    <h2 id="archive-heading" class="font-semibold">Archive project</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Remove it from active views while retaining environments and history.
                    </p>
                </div>
                <button
                    class="self-start rounded-lg border border-red-300 px-4 py-2 font-semibold text-danger"
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
                    class="rounded-lg bg-danger px-4 py-2 font-semibold text-on-danger disabled:opacity-60"
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
