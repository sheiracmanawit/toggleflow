<script setup lang="ts">
import axios from 'axios';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

import { dashboardService } from '../services';
import type { DashboardSummary, RecentActivity } from '../types';

const summary = ref<DashboardSummary | null>(null);
const isLoading = ref(true);
const loadError = ref('');
let controller: AbortController | null = null;

const actionLabels: Record<string, string> = {
    'project.created': 'created',
    'project.archived': 'archived',
    'feature_flag.created': 'created',
    'feature_flag.updated': 'updated',
    'feature_flag.archived': 'archived',
    'feature_flag.enabled': 'enabled',
    'feature_flag.disabled': 'disabled',
    'api_key.created': 'created',
    'api_key.revoked': 'revoked',
};

const activityDescription = (activity: RecentActivity): string => {
    const actor = activity.actor?.name ?? 'System';
    const action = actionLabels[activity.action] ?? activity.action;
    const environment = activity.environment?.name ? ` in ${activity.environment.name}` : '';
    return `${actor} ${action} ${activity.subject.name}${environment}`;
};

const formatTime = (value: string): string =>
    new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

const load = async (): Promise<void> => {
    controller?.abort();
    controller = new AbortController();
    isLoading.value = true;
    loadError.value = '';
    summary.value = null;

    try {
        summary.value = await dashboardService.getSummary(controller.signal);
    } catch (error: unknown) {
        if (!axios.isCancel(error)) {
            loadError.value = 'Current release state could not be loaded. No previous values are shown as current.';
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(load);
onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <section aria-labelledby="dashboard-heading">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand">Release overview</p>
            <h1 id="dashboard-heading" class="mt-1 text-3xl font-bold">Dashboard</h1>
            <p class="mt-2 max-w-2xl text-slate-600">
                Confirm what is active before making your next release decision.
            </p>
        </div>

        <div v-if="isLoading" class="mt-8 grid gap-4 sm:grid-cols-3" role="status" aria-label="Loading release state">
            <div
                v-for="index in 3"
                :key="index"
                class="h-28 animate-pulse rounded-2xl bg-slate-200 motion-reduce:animate-none"
            />
        </div>

        <div v-else-if="loadError" class="mt-8 rounded-2xl border border-red-200 bg-red-50 p-6" role="alert">
            <h2 class="font-semibold text-danger">Release state unavailable</h2>
            <p class="mt-2 text-sm text-slate-700">{{ loadError }}</p>
            <button class="mt-4 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>

        <template v-else-if="summary">
            <dl class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <dt class="text-sm font-medium text-slate-600">Active projects</dt>
                    <dd class="mt-2 text-3xl font-bold tabular-nums">{{ summary.project_count }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <dt class="text-sm font-medium text-slate-600">Active flags</dt>
                    <dd class="mt-2 text-3xl font-bold tabular-nums">{{ summary.active_flag_count }}</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <dt class="text-sm font-medium text-slate-600">Enabled in Production</dt>
                    <dd class="mt-2 text-3xl font-bold tabular-nums">{{ summary.production_enabled_count }}</dd>
                </div>
            </dl>

            <div v-if="summary.project_count === 0" class="mt-8 rounded-2xl border border-slate-200 bg-white p-8">
                <h2 class="text-xl font-semibold">Create your first project</h2>
                <p class="mt-2 text-slate-600">
                    A project gives you isolated Development, Staging, and Production release state.
                </p>
                <RouterLink
                    class="mt-4 inline-flex rounded-lg bg-brand px-4 py-2 font-semibold text-white"
                    to="/projects"
                >
                    Create a project
                </RouterLink>
            </div>

            <div v-else class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(18rem,2fr)]">
                <section aria-labelledby="projects-heading">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="projects-heading" class="text-xl font-semibold">Projects</h2>
                        <RouterLink class="text-sm font-semibold text-brand hover:underline" to="/projects">
                            View all
                        </RouterLink>
                    </div>
                    <ul class="mt-4 grid gap-4">
                        <li
                            v-for="project in summary.projects"
                            :key="project.id"
                            class="rounded-2xl border border-slate-200 bg-white p-5"
                        >
                            <RouterLink
                                class="font-semibold text-brand hover:underline"
                                :to="`/projects/${project.id}`"
                            >
                                {{ project.name }}
                            </RouterLink>
                            <p class="mt-1 font-mono text-sm text-slate-500">{{ project.slug }}</p>
                            <p class="mt-3 text-sm text-slate-700">
                                {{ project.active_flag_count }} active flags ·
                                {{ project.production_enabled_count }} enabled in Production
                            </p>
                        </li>
                    </ul>
                </section>

                <section aria-labelledby="activity-heading">
                    <h2 id="activity-heading" class="text-xl font-semibold">Recent activity</h2>
                    <p
                        v-if="summary.recent_activity.length === 0"
                        class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600"
                    >
                        Release-management changes will appear here.
                    </p>
                    <ol v-else class="mt-4 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white px-5">
                        <li v-for="activity in summary.recent_activity" :key="activity.id" class="py-4">
                            <p class="text-sm font-medium">{{ activityDescription(activity) }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ activity.project.name }} ·
                                <time :datetime="activity.created_at">{{ formatTime(activity.created_at) }}</time>
                            </p>
                        </li>
                    </ol>
                </section>
            </div>
        </template>
    </section>
</template>
