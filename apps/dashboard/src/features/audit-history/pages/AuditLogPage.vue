<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

import { auditEventService, type AuditEvent } from '@features/audit-history';

type BadgeColor = 'error' | 'info' | 'neutral' | 'primary' | 'secondary' | 'success' | 'warning';

const route = useRoute();
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
const shortTimestamp = (value: string): string =>
    new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
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
    })[action] ?? 'Changed configuration';
const actionColor = (action: string): BadgeColor => {
    if (action === 'feature_flag.enabled') return 'success';
    if (action === 'feature_flag.disabled') return 'neutral';
    if (action.endsWith('.archived') || action === 'api_key.revoked') return 'error';
    if (action.endsWith('.updated')) return 'info';
    if (action.endsWith('.created')) return 'primary';

    return 'neutral';
};
const actionIcon = (action: string): string => {
    if (action === 'feature_flag.enabled') return 'i-lucide-circle-check';
    if (action === 'feature_flag.disabled') return 'i-lucide-circle-minus';
    if (action.endsWith('.archived')) return 'i-lucide-archive';
    if (action === 'api_key.revoked') return 'i-lucide-key-round';
    if (action.endsWith('.created')) return 'i-lucide-plus';
    if (action.endsWith('.updated')) return 'i-lucide-pencil';

    return 'i-lucide-history';
};
const subjectTypeLabel = (type: string): string =>
    ({ FeatureFlag: 'Feature flag', ApiKey: 'API key', Project: 'Project' })[type] ?? 'Release resource';
const subjectDestination = (event: AuditEvent): string | null => {
    if (event.subject.type === 'FeatureFlag') return `/projects/${event.project.id}/flags/${event.subject.id}`;
    if (event.subject.type === 'ApiKey') return `/projects/${event.project.id}/api-keys`;
    if (event.subject.type === 'Project') return `/projects/${event.project.id}`;

    return null;
};
const environmentLabel = (event: AuditEvent): string | null =>
    event.environment?.name ?? event.environment?.key ?? null;
const environmentColor = (event: AuditEvent): BadgeColor => {
    if (event.environment?.key === 'development') return 'info';
    if (event.environment?.key === 'staging') return 'warning';

    return 'neutral';
};
const productionBadgeClass = (event: AuditEvent): string | undefined =>
    event.environment?.key === 'production'
        ? 'bg-environment-production/10 text-environment-production ring-environment-production/30'
        : undefined;
const actorInitial = (event: AuditEvent): string => (event.actor?.name || 'System').charAt(0).toUpperCase();

const pageLabel = computed(() => `Page ${page.value} of ${lastPage.value}`);
const isInitialLoading = computed(() => isLoading.value && loadedProjectId === null);

const load = async (requestedPage = 1): Promise<void> => {
    controller?.abort();
    controller = new AbortController();
    const requestId = ++activeRequest;
    const projectId = Number(route.params.projectId);
    if (loadedProjectId !== null && loadedProjectId !== projectId) {
        loadedProjectId = null;
        events.value = [];
        page.value = 1;
        lastPage.value = 1;
        total.value = 0;
    }
    isLoading.value = true;
    error.value = '';
    try {
        const history = await auditEventService.list(projectId, requestedPage, controller.signal);
        if (requestId !== activeRequest || Number(route.params.projectId) !== projectId) return;
        loadedProjectId = projectId;
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
        <div v-if="isInitialLoading" class="grid gap-3 p-6" role="status" aria-live="polite">
            <p>Loading audit history…</p>
            <div
                v-for="item in 4"
                :key="item"
                class="h-20 animate-pulse rounded-lg bg-surface-muted"
                aria-hidden="true"
            />
        </div>
        <div
            v-else-if="error && events.length === 0"
            class="m-6 rounded-lg border border-red-200 bg-red-50 p-4"
            role="alert"
        >
            <h2 class="text-base font-semibold">Audit history unavailable</h2>
            <p class="mt-2 text-sm">{{ error }}</p>
            <UButton
                class="mt-4"
                color="error"
                icon="i-lucide-refresh-cw"
                type="button"
                variant="outline"
                @click="load(page)"
            >
                Try again
            </UButton>
        </div>
        <div v-if="!isInitialLoading && !error && events.length === 0" class="p-6" role="status">
            <div class="rounded-lg border border-dashed border-border px-6 py-12 text-center">
                <UIcon name="i-lucide-history" aria-hidden="true" class="mx-auto size-7 text-text-muted" />
                <h2 class="mt-3 text-base font-semibold">No management changes yet</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-text-muted">
                    Project, feature flag, environment state, and API key changes will appear here.
                </p>
            </div>
        </div>
        <template v-if="!isInitialLoading && events.length > 0">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6">
                <div>
                    <p class="text-sm font-semibold text-text">Management activity</p>
                    <p class="mt-0.5 text-sm text-text-muted">Newest changes first</p>
                </div>
                <UBadge color="neutral" icon="i-lucide-history" variant="subtle">
                    {{ total }} {{ total === 1 ? 'event' : 'events' }}
                </UBadge>
            </div>
            <p
                v-if="isLoading"
                class="border-y border-border px-4 py-3 text-sm font-medium text-brand sm:px-6"
                role="status"
                aria-live="polite"
            >
                Refreshing audit history. Previously loaded results remain visible.
            </p>
            <div
                v-if="error"
                class="mx-4 mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm sm:mx-6"
                role="alert"
            >
                <p>{{ error }}</p>
                <UButton
                    class="mt-3"
                    color="error"
                    icon="i-lucide-refresh-cw"
                    size="sm"
                    type="button"
                    variant="outline"
                    @click="load(page)"
                >
                    Try again
                </UButton>
            </div>

            <section class="px-4 pb-6 sm:px-6" aria-label="Audit events">
                <div class="hidden overflow-x-auto rounded-lg border border-border xl:block">
                    <table class="min-w-[52rem] w-full border-collapse text-left text-sm">
                        <thead class="border-b border-border bg-surface-muted text-text">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Activity</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Subject</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Actor</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Environment</th>
                                <th class="px-5 py-3.5 text-right font-semibold" scope="col">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="event in events" :key="event.id" class="hover:bg-surface-muted/50">
                                <td class="px-5 py-4 align-middle">
                                    <UBadge
                                        :color="actionColor(event.action)"
                                        :icon="actionIcon(event.action)"
                                        variant="subtle"
                                    >
                                        {{ actionLabel(event.action) }}
                                    </UBadge>
                                </td>
                                <th class="px-5 py-4 align-middle font-normal" scope="row">
                                    <RouterLink
                                        v-if="subjectDestination(event)"
                                        class="font-semibold text-text hover:text-brand hover:underline"
                                        :to="subjectDestination(event)!"
                                    >
                                        {{ event.subject.name }}
                                    </RouterLink>
                                    <span v-else class="font-semibold text-text">{{ event.subject.name }}</span>
                                    <span class="mt-0.5 block text-xs text-text-muted">{{
                                        subjectTypeLabel(event.subject.type)
                                    }}</span>
                                </th>
                                <td class="px-5 py-4 align-middle">
                                    <span class="flex items-center gap-2.5">
                                        <span
                                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-xs font-semibold text-brand"
                                        >
                                            {{ actorInitial(event) }}
                                        </span>
                                        <span class="font-medium text-text">{{ event.actor?.name || 'System' }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <UBadge
                                        v-if="environmentLabel(event)"
                                        :class="productionBadgeClass(event)"
                                        :color="environmentColor(event)"
                                        variant="subtle"
                                    >
                                        {{ environmentLabel(event) }}
                                    </UBadge>
                                    <span v-else class="text-sm text-text-muted">Project-wide</span>
                                </td>
                                <td class="px-5 py-4 text-right align-middle text-text-muted">
                                    <time
                                        :datetime="event.created_at"
                                        :title="timestamp(event.created_at)"
                                        :aria-label="timestamp(event.created_at)"
                                    >
                                        <span class="block font-medium text-text">{{
                                            relativeTime(event.created_at)
                                        }}</span>
                                        <span class="mt-0.5 block text-xs">{{ shortTimestamp(event.created_at) }}</span>
                                    </time>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <ol class="divide-y divide-border overflow-hidden rounded-lg border border-border xl:hidden">
                    <li v-for="event in events" :key="event.id" class="p-4">
                        <article>
                            <div class="flex items-start justify-between gap-3">
                                <UBadge
                                    :color="actionColor(event.action)"
                                    :icon="actionIcon(event.action)"
                                    variant="subtle"
                                >
                                    {{ actionLabel(event.action) }}
                                </UBadge>
                                <time
                                    class="shrink-0 text-xs text-text-muted"
                                    :datetime="event.created_at"
                                    :title="timestamp(event.created_at)"
                                    :aria-label="timestamp(event.created_at)"
                                >
                                    {{ relativeTime(event.created_at) }}
                                </time>
                            </div>
                            <div class="mt-4">
                                <RouterLink
                                    v-if="subjectDestination(event)"
                                    class="font-semibold text-text hover:text-brand hover:underline"
                                    :to="subjectDestination(event)!"
                                >
                                    {{ event.subject.name }}
                                </RouterLink>
                                <span v-else class="font-semibold text-text">{{ event.subject.name }}</span>
                                <span class="mt-0.5 block text-xs text-text-muted">{{
                                    subjectTypeLabel(event.subject.type)
                                }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-text-muted">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-soft text-xs font-semibold text-brand"
                                    >
                                        {{ actorInitial(event) }}
                                    </span>
                                    {{ event.actor?.name || 'System' }}
                                </span>
                                <span aria-hidden="true">·</span>
                                <UBadge
                                    v-if="environmentLabel(event)"
                                    :class="productionBadgeClass(event)"
                                    :color="environmentColor(event)"
                                    size="sm"
                                    variant="subtle"
                                >
                                    {{ environmentLabel(event) }}
                                </UBadge>
                                <span v-else>Project-wide</span>
                            </div>
                        </article>
                    </li>
                </ol>

                <nav
                    v-if="lastPage > 1"
                    class="mt-4 flex items-center justify-between gap-4"
                    aria-label="Audit history pages"
                >
                    <UButton
                        icon="i-lucide-chevron-left"
                        type="button"
                        variant="outline"
                        :disabled="page <= 1 || isLoading"
                        @click="load(page - 1)"
                    >
                        Previous
                    </UButton>
                    <span class="text-sm text-text-muted">{{ pageLabel }}</span>
                    <UButton
                        icon="i-lucide-chevron-right"
                        trailing
                        type="button"
                        variant="outline"
                        :disabled="page >= lastPage || isLoading"
                        @click="load(page + 1)"
                    >
                        Next
                    </UButton>
                </nav>
            </section>
        </template>
    </section>
</template>
