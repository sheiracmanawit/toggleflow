<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

import { auditEventService, projectService } from '../services';
import type { AuditEvent, Project } from '../types';
import { auditEventDescription } from '../utils/auditEvents';

const route = useRoute();
const project = ref<Project | null>(null);
const events = ref<AuditEvent[]>([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const isLoading = ref(true);
const error = ref('');
let controller: AbortController | null = null;
let activeRequest = 0;
let loadedProjectId: number | null = null;

const timestamp = (value: string): string =>
    new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'long' }).format(new Date(value));

const pageLabel = computed(() => `Page ${page.value} of ${lastPage.value}`);
const isInitialLoading = computed(() => isLoading.value && loadedProjectId === null);

const load = async (requestedPage = 1): Promise<void> => {
    controller?.abort();
    controller = new AbortController();
    const requestId = ++activeRequest;
    const projectId = Number(route.params.projectId);
    if (loadedProjectId !== null && loadedProjectId !== projectId) {
        loadedProjectId = null;
        project.value = null;
        events.value = [];
        page.value = 1;
        lastPage.value = 1;
        total.value = 0;
    }
    isLoading.value = true;
    error.value = '';
    try {
        const [loadedProject, history] = await Promise.all([
            projectService.get(projectId, controller.signal),
            auditEventService.list(projectId, requestedPage, controller.signal),
        ]);
        if (requestId !== activeRequest || Number(route.params.projectId) !== projectId) return;
        loadedProjectId = projectId;
        project.value = loadedProject;
        events.value = history.events;
        page.value = history.currentPage;
        lastPage.value = history.lastPage;
        total.value = history.total;
    } catch (reason: unknown) {
        if (requestId === activeRequest && !axios.isCancel(reason)) {
            error.value =
                loadedProjectId === projectId
                    ? 'Audit history could not be refreshed. Previously loaded results are shown below.'
                    : 'Audit history could not be loaded.';
        }
    } finally {
        if (requestId === activeRequest && Number(route.params.projectId) === projectId) isLoading.value = false;
    }
};

watch(
    () => route.params.projectId,
    () => load(1),
    { immediate: true },
);
onBeforeUnmount(() => {
    activeRequest += 1;
    controller?.abort();
});
</script>

<template>
    <section aria-labelledby="audit-log-heading">
        <RouterLink
            class="text-sm font-semibold text-brand hover:underline"
            :to="`/projects/${route.params.projectId}`"
        >
            ← Project overview
        </RouterLink>
        <div class="mt-6">
            <p v-if="project" class="text-sm font-semibold uppercase tracking-wide text-brand">{{ project.name }}</p>
            <h1 id="audit-log-heading" class="mt-1 text-3xl font-bold">Audit history</h1>
            <p class="mt-2 max-w-2xl text-slate-600">Release-management changes are shown newest first.</p>
        </div>

        <div v-if="isInitialLoading" class="mt-8 grid gap-3" role="status" aria-live="polite">
            <p>Loading audit history…</p>
            <div v-for="item in 3" :key="item" class="h-28 animate-pulse rounded-xl bg-slate-200" aria-hidden="true" />
        </div>
        <div v-else-if="error" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h2 class="text-lg font-semibold">Audit history unavailable</h2>
            <p class="mt-2">{{ error }}</p>
            <button
                class="mt-4 rounded-lg border border-red-300 px-4 py-2 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2"
                type="button"
                @click="load(page)"
            >
                Try again
            </button>
        </div>
        <div
            v-if="!isInitialLoading && !error && events.length === 0"
            class="mt-8 rounded-xl border border-slate-200 bg-white p-6"
            role="status"
        >
            <h2 class="text-lg font-semibold">No management changes yet</h2>
            <p class="mt-2 text-slate-600">
                Events will appear after a project, feature flag, environment state, or API key changes.
            </p>
        </div>
        <template v-if="!isInitialLoading && events.length > 0">
            <p v-if="isLoading" class="mt-6 text-sm font-medium text-brand" role="status" aria-live="polite">
                Refreshing audit history. Previously loaded results remain visible.
            </p>
            <p class="mt-6 text-sm text-slate-600" role="status">{{ total }} events · {{ pageLabel }}</p>
            <ol class="mt-4 grid gap-4">
                <li v-for="event in events" :key="event.id" class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words font-semibold">{{ auditEventDescription(event) }}</p>
                            <p class="mt-1 text-sm text-slate-500">Project: {{ event.project.name }}</p>
                        </div>
                        <time class="shrink-0 text-sm text-slate-600" :datetime="event.created_at">{{
                            timestamp(event.created_at)
                        }}</time>
                    </div>
                </li>
            </ol>
            <nav
                v-if="lastPage > 1"
                class="mt-6 flex items-center justify-between gap-4"
                aria-label="Audit history pages"
            >
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold disabled:opacity-50"
                    type="button"
                    :disabled="page <= 1 || isLoading"
                    @click="load(page - 1)"
                >
                    Previous
                </button>
                <span class="text-sm">{{ pageLabel }}</span>
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold disabled:opacity-50"
                    type="button"
                    :disabled="page >= lastPage || isLoading"
                    @click="load(page + 1)"
                >
                    Next
                </button>
            </nav>
        </template>
    </section>
</template>
