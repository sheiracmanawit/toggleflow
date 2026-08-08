<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

import { auditEventDescription, auditEventService, type AuditEvent } from '@features/audit-history';
import { projectService, type Project } from '@features/projects';

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
const relativeTime = (value: string): string => {
    const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000);
    const intervals: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
    ];
    const [unit, divisor] = intervals.find(([, size]) => Math.abs(seconds) >= size) ?? ['second', 1];

    return new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' }).format(Math.round(seconds / divisor), unit);
};
const actionLabel = (action: string): string =>
    ({
        'project.created': 'Created project',
        'project.updated': 'Updated project',
        'project.archived': 'Archived project',
        'feature_flag.created': 'Created feature flag',
        'feature_flag.updated': 'Updated feature flag',
        'feature_flag.archived': 'Archived feature flag',
        'feature_flag.enabled': 'Enabled feature flag',
        'feature_flag.disabled': 'Disabled feature flag',
        'api_key.created': 'Issued API key',
        'api_key.revoked': 'Revoked API key',
    })[action] ?? 'Changed release configuration';
const subjectDestination = (event: AuditEvent): string | null => {
    if (event.subject.type === 'FeatureFlag') return `/projects/${event.project.id}/flags/${event.subject.id}`;
    if (event.subject.type === 'ApiKey') return `/projects/${event.project.id}/api-keys`;
    if (event.subject.type === 'Project') return `/projects/${event.project.id}`;

    return null;
};

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
    <section class="-mx-4 -my-6 sm:-mx-6 sm:-my-8" aria-label="Audit history">
        <UDashboardToolbar class="border-b border-border px-4 py-3 sm:px-6">
            <template #left>
                <RouterLink
                    class="text-sm font-medium text-text-muted hover:text-text"
                    :to="`/projects/${route.params.projectId}`"
                >
                    ← Project overview
                </RouterLink>
                <span class="text-sm text-text-muted">Release changes, newest first</span>
            </template>
            <template #right>
                <span v-if="!isInitialLoading" class="text-sm text-text-muted">{{ total }} events</span>
            </template>
        </UDashboardToolbar>

        <div v-if="isInitialLoading" class="grid gap-3 p-6" role="status" aria-live="polite">
            <p>Loading audit history…</p>
            <div v-for="item in 3" :key="item" class="h-28 animate-pulse rounded-xl bg-slate-200" aria-hidden="true" />
        </div>
        <div v-else-if="error" class="m-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
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
        <div v-if="!isInitialLoading && !error && events.length === 0" class="p-6" role="status">
            <h2 class="text-lg font-semibold">No management changes yet</h2>
            <p class="mt-2 text-slate-600">
                Events will appear after a project, feature flag, environment state, or API key changes.
            </p>
        </div>
        <template v-if="!isInitialLoading && events.length > 0">
            <p
                v-if="isLoading"
                class="border-b border-border px-4 py-3 text-sm font-medium text-brand sm:px-6"
                role="status"
                aria-live="polite"
            >
                Refreshing audit history. Previously loaded results remain visible.
            </p>
            <ol class="divide-y divide-border">
                <li v-for="event in events" :key="event.id" class="px-4 py-3 sm:px-6">
                    <article
                        class="grid gap-2 sm:grid-cols-[minmax(11rem,0.7fr)_minmax(0,1fr)_auto] sm:items-center sm:gap-4"
                    >
                        <div class="min-w-0">
                            <span class="text-sm font-semibold">{{ actionLabel(event.action) }}</span>
                            <span v-if="event.environment" class="mt-0.5 block text-xs text-slate-500">
                                {{ event.environment.name || event.environment.key }} environment
                            </span>
                        </div>
                        <p class="min-w-0 text-sm text-slate-600">
                            <span class="font-medium text-slate-900">{{ event.actor?.name || 'System' }}</span>
                            changed
                            <RouterLink
                                v-if="subjectDestination(event)"
                                class="font-semibold text-brand hover:underline"
                                :to="subjectDestination(event)!"
                            >
                                {{ event.subject.name }}
                            </RouterLink>
                            <span v-else class="font-semibold text-slate-900">{{ event.subject.name }}</span>
                            <span class="sr-only">. {{ auditEventDescription(event) }}</span>
                        </p>
                        <time
                            class="shrink-0 text-xs text-slate-600 sm:text-right"
                            :datetime="event.created_at"
                            :title="timestamp(event.created_at)"
                            :aria-label="timestamp(event.created_at)"
                        >
                            {{ relativeTime(event.created_at) }}
                        </time>
                    </article>
                </li>
            </ol>
            <nav
                v-if="lastPage > 1"
                class="flex items-center justify-between gap-4 border-t border-border px-4 py-3 sm:px-6"
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
