<script setup lang="ts">
import axios from 'axios';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

import { auditEventDescription } from '@features/audit-history';
import { dashboardService } from '@features/dashboard';
import type { DashboardSummary, RecentActivity } from '@features/dashboard';

const summary = ref<DashboardSummary | null>(null);
const isLoading = ref(true);
const loadError = ref('');
let controller: AbortController | null = null;
let activeRequest = 0;

const activityDescription = (activity: RecentActivity): string => auditEventDescription(activity);

const formatTime = (value: string): string =>
    new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

const load = async (): Promise<void> => {
    controller?.abort();
    controller = new AbortController();
    const requestId = ++activeRequest;
    isLoading.value = true;
    loadError.value = '';
    summary.value = null;

    try {
        const loadedSummary = await dashboardService.getSummary(controller.signal);
        if (requestId === activeRequest) summary.value = loadedSummary;
    } catch (error: unknown) {
        if (requestId === activeRequest && !axios.isCancel(error)) {
            loadError.value = 'Current release state could not be loaded. No previous values are shown as current.';
        }
    } finally {
        if (requestId === activeRequest) isLoading.value = false;
    }
};

onMounted(load);
onBeforeUnmount(() => {
    activeRequest += 1;
    controller?.abort();
});
</script>

<template>
    <section class="mx-auto max-w-7xl" aria-labelledby="dashboard-heading">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">Release overview</p>
                <h1 id="dashboard-heading" class="mt-1 text-3xl font-bold text-text sm:text-4xl">Dashboard</h1>
                <p class="mt-2 max-w-2xl text-text-muted">
                    See where releases stand and choose the project that needs your attention.
                </p>
            </div>
            <UButton to="/projects" icon="i-lucide-folder-kanban" class="self-start sm:self-auto">
                View all projects
            </UButton>
        </header>

        <div v-if="isLoading" class="mt-8" role="status" aria-live="polite" aria-label="Loading release state">
            <span class="sr-only">Loading release state…</span>
            <div class="grid gap-4 sm:grid-cols-3" aria-hidden="true">
                <USkeleton v-for="index in 3" :key="index" class="h-28 rounded-xl motion-reduce:animate-none" />
            </div>
            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(18rem,2fr)]" aria-hidden="true">
                <USkeleton class="h-72 rounded-xl motion-reduce:animate-none" />
                <USkeleton class="h-72 rounded-xl motion-reduce:animate-none" />
            </div>
        </div>

        <UAlert
            v-else-if="loadError"
            class="mt-8"
            color="error"
            icon="i-lucide-circle-alert"
            title="Release state unavailable"
            :description="loadError"
            role="alert"
        >
            <template #actions>
                <UButton color="error" variant="soft" icon="i-lucide-refresh-cw" @click="load">Try again</UButton>
            </template>
        </UAlert>

        <template v-else-if="summary">
            <div class="mt-8 grid gap-4 sm:grid-cols-3" aria-label="Portfolio release state">
                <UCard as="div" variant="subtle">
                    <dl>
                        <dt class="flex items-center gap-2 text-sm font-medium text-text-muted">
                            <UIcon name="i-lucide-folder-kanban" aria-hidden="true" class="size-4 text-brand" />
                            Active projects
                        </dt>
                        <dd class="mt-2 text-3xl font-bold tabular-nums text-text">{{ summary.project_count }}</dd>
                    </dl>
                </UCard>
                <UCard as="div" variant="subtle">
                    <dl>
                        <dt class="flex items-center gap-2 text-sm font-medium text-text-muted">
                            <UIcon name="i-lucide-flag" aria-hidden="true" class="size-4 text-brand" />
                            Active flags
                        </dt>
                        <dd class="mt-2 text-3xl font-bold tabular-nums text-text">{{ summary.active_flag_count }}</dd>
                    </dl>
                </UCard>
                <UCard as="div" variant="subtle" class="border-environment-production/40">
                    <dl>
                        <dt class="flex items-center gap-2 text-sm font-medium text-text-muted">
                            <UIcon
                                name="i-lucide-rocket"
                                aria-hidden="true"
                                class="size-4 text-environment-production"
                            />
                            Enabled in Production
                        </dt>
                        <dd class="mt-2 text-3xl font-bold tabular-nums text-text">
                            {{ summary.production_enabled_count }}
                        </dd>
                    </dl>
                </UCard>
            </div>

            <UCard
                v-if="summary.project_count === 0 && summary.recent_activity.length === 0"
                class="mt-8"
                variant="subtle"
            >
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand">
                        <UIcon name="i-lucide-folder-plus" aria-hidden="true" class="size-6" />
                    </span>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-text">Create your first project</h2>
                        <p class="mt-1 text-text-muted">
                            A project gives you isolated Development, Staging, and Production release state.
                        </p>
                    </div>
                    <UButton to="/projects" icon="i-lucide-plus">Create a project</UButton>
                </div>
            </UCard>

            <div v-else class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(18rem,2fr)]">
                <section aria-labelledby="projects-heading">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 id="projects-heading" class="text-xl font-semibold text-text">Projects</h2>
                            <p class="mt-1 text-sm text-text-muted">Current flag state by active project.</p>
                        </div>
                        <RouterLink class="shrink-0 text-sm font-semibold text-brand hover:underline" to="/projects">
                            View all
                        </RouterLink>
                    </div>
                    <UCard v-if="summary.projects.length === 0" class="mt-4" variant="subtle">
                        <h3 class="font-semibold text-text">No active projects</h3>
                        <p class="mt-1 text-sm text-text-muted">
                            Archived project history remains available from recent activity.
                        </p>
                        <UButton to="/projects" class="mt-4" size="sm" icon="i-lucide-folder-plus">
                            Create a project
                        </UButton>
                    </UCard>
                    <ul
                        v-else
                        class="mt-4 divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface"
                    >
                        <li v-for="project in summary.projects" :key="project.id" class="p-5 sm:p-6">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <RouterLink
                                        class="text-lg font-semibold text-text hover:text-brand hover:underline"
                                        :to="`/projects/${project.id}`"
                                    >
                                        {{ project.name }}
                                    </RouterLink>
                                    <p class="mt-1 truncate font-mono text-xs text-text-muted">{{ project.slug }}</p>
                                    <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-3 text-sm">
                                        <div>
                                            <dt class="text-text-muted">Active flags</dt>
                                            <dd class="mt-0.5 font-semibold tabular-nums text-text">
                                                {{ project.active_flag_count }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="flex items-center gap-1.5 text-text-muted">
                                                <span
                                                    class="size-2 rounded-full bg-environment-production"
                                                    aria-hidden="true"
                                                />
                                                Production enabled
                                            </dt>
                                            <dd class="mt-0.5 font-semibold tabular-nums text-text">
                                                {{ project.production_enabled_count }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <UButton
                                        :to="`/projects/${project.id}`"
                                        color="neutral"
                                        variant="soft"
                                        size="sm"
                                        icon="i-lucide-git-compare-arrows"
                                    >
                                        View release state
                                    </UButton>
                                    <UButton
                                        :to="`/projects/${project.id}/audit-log`"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        icon="i-lucide-history"
                                    >
                                        Recent changes
                                    </UButton>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="activity-heading">
                    <div>
                        <h2 id="activity-heading" class="text-xl font-semibold text-text">Recent activity</h2>
                        <p class="mt-1 text-sm text-text-muted">Newest management changes across your projects.</p>
                    </div>
                    <UCard v-if="summary.recent_activity.length === 0" class="mt-4" variant="subtle">
                        <div class="flex gap-3">
                            <UIcon
                                name="i-lucide-history"
                                aria-hidden="true"
                                class="mt-0.5 size-5 shrink-0 text-brand"
                            />
                            <div>
                                <h3 class="font-semibold text-text">No recent changes</h3>
                                <p class="mt-1 text-sm text-text-muted">Release-management changes will appear here.</p>
                            </div>
                        </div>
                    </UCard>
                    <ol
                        v-else
                        class="mt-4 divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface"
                    >
                        <li v-for="activity in summary.recent_activity" :key="activity.id">
                            <RouterLink
                                class="group block p-4 hover:bg-surface-muted sm:p-5"
                                :to="`/projects/${activity.project.id}/audit-log`"
                                :aria-label="`${activityDescription(activity)}. View ${activity.project.name} audit history`"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            activity.environment?.key === 'production'
                                                ? 'bg-environment-production/15 text-environment-production'
                                                : 'bg-brand-soft text-brand'
                                        "
                                    >
                                        <UIcon
                                            :name="
                                                activity.environment?.key === 'production'
                                                    ? 'i-lucide-rocket'
                                                    : 'i-lucide-history'
                                            "
                                            aria-hidden="true"
                                            class="size-4"
                                        />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-medium text-text">
                                                {{ activityDescription(activity) }}
                                            </p>
                                            <UBadge
                                                v-if="activity.environment?.key === 'production'"
                                                color="neutral"
                                                variant="soft"
                                                size="sm"
                                                class="text-environment-production"
                                            >
                                                Production
                                            </UBadge>
                                        </div>
                                        <p class="mt-1 text-xs text-text-muted">
                                            {{ activity.project.name }} ·
                                            <time :datetime="activity.created_at">{{
                                                formatTime(activity.created_at)
                                            }}</time>
                                        </p>
                                    </div>
                                    <UIcon
                                        name="i-lucide-chevron-right"
                                        aria-hidden="true"
                                        class="mt-1 size-4 shrink-0 text-text-muted group-hover:text-brand"
                                    />
                                </div>
                            </RouterLink>
                        </li>
                    </ol>
                </section>
            </div>
        </template>
    </section>
</template>
