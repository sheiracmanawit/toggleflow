<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import { useProjectCreation } from '../composables/useProjectCreation';

const {
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
} = useProjectCreation();
</script>

<template>
    <section aria-labelledby="projects-heading">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">Applications</p>
                <h1 id="projects-heading" class="mt-1 text-3xl font-bold">Projects</h1>
                <p class="mt-2 max-w-2xl text-slate-600">
                    A project represents one application or service whose releases you manage with ToggleFlow.
                </p>
            </div>
            <button
                class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand hover:bg-brand-hover"
                type="button"
                @click="openCreate"
            >
                Create project
            </button>
        </div>

        <p v-if="isLoading" class="mt-8 rounded-xl border border-slate-200 bg-white p-6" role="status">
            Loading projects…
        </p>

        <div v-else-if="loadError" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <p>{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>

        <div
            v-else-if="projects.length === 0"
            class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"
        >
            <h2 class="text-xl font-semibold">Create your first application project</h2>
            <p class="mx-auto mt-2 max-w-xl text-slate-600">
                Development, Staging, and Production are added automatically so release contexts stay isolated.
            </p>
            <button
                class="mt-5 rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand"
                type="button"
                @click="openCreate"
            >
                Create project
            </button>
        </div>

        <ul v-else class="mt-8 grid gap-4 md:grid-cols-2">
            <li v-for="project in projects" :key="project.id">
                <RouterLink
                    class="block h-full rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-brand hover:shadow-sm"
                    :to="`/projects/${project.id}`"
                >
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-lg font-semibold">{{ project.name }}</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                            >Active</span
                        >
                    </div>
                    <p class="mt-1 font-mono text-sm text-slate-500">{{ project.slug }}</p>
                    <p class="mt-4 text-sm text-slate-600">
                        {{ project.description || 'No project description yet.' }}
                    </p>
                    <p class="mt-5 text-xs text-slate-500">
                        Updated {{ new Date(project.updated_at).toLocaleString() }}
                    </p>
                </RouterLink>
            </li>
        </ul>
    </section>

    <AppDialog
        v-if="showCreateForm"
        title="Create a project"
        description="Development, Staging, and Production will be created in the same successful operation."
        @cancel="!isSubmitting && (showCreateForm = false)"
    >
        <form class="grid gap-5" novalidate @submit.prevent="submit">
            <div>
                <label class="block text-sm font-semibold" for="project-name">Name</label>
                <input
                    id="project-name"
                    v-model="form.name"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                    :aria-describedby="validationErrors.name ? 'project-name-error' : undefined"
                    :aria-invalid="Boolean(validationErrors.name)"
                    autocomplete="off"
                />
                <p v-if="validationErrors.name" id="project-name-error" class="mt-1 text-sm text-danger">
                    {{ validationErrors.name[0] }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold" for="project-slug">Machine-readable slug</label>
                <input
                    id="project-slug"
                    v-model="form.slug"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono"
                    :placeholder="suggestedSlug || 'checkout-service'"
                    :aria-describedby="validationErrors.slug ? 'project-slug-error' : 'project-slug-help'"
                    :aria-invalid="Boolean(validationErrors.slug)"
                    autocomplete="off"
                />
                <p id="project-slug-help" class="mt-1 text-xs text-slate-500">
                    Lowercase letters, numbers, and hyphens. The slug cannot be changed later.
                </p>
                <p v-if="validationErrors.slug" id="project-slug-error" class="mt-1 text-sm text-danger">
                    {{ validationErrors.slug[0] }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold" for="project-description">Description (optional)</label>
                <textarea
                    id="project-description"
                    v-model="form.description"
                    class="mt-1 min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2"
                    :aria-describedby="validationErrors.description ? 'project-description-error' : undefined"
                    :aria-invalid="Boolean(validationErrors.description)"
                />
                <p v-if="validationErrors.description" id="project-description-error" class="mt-1 text-sm text-danger">
                    {{ validationErrors.description[0] }}
                </p>
            </div>
            <p v-if="submitError" class="text-sm text-danger" role="alert">{{ submitError }}</p>
            <div class="flex flex-wrap gap-3">
                <button
                    class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand disabled:cursor-wait disabled:opacity-60"
                    type="submit"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? 'Creating project…' : 'Create project' }}
                </button>
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                    type="button"
                    :disabled="isSubmitting"
                    @click="showCreateForm = false"
                >
                    Cancel
                </button>
            </div>
        </form>
    </AppDialog>
</template>
