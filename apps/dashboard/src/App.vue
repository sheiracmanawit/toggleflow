<script setup lang="ts">
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';

import { projectService } from './services';
import { useAuthStore } from './stores';
import type { ProjectSummary } from './types';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const drawerOpen = ref(false);
const drawer = ref<HTMLElement | null>(null);
const drawerButton = ref<HTMLElement | null>(null);
const isSigningOut = ref(false);
const signOutError = ref('');
const projects = ref<ProjectSummary[]>([]);
const projectsLoading = ref(false);
const projectsError = ref('');
let projectsController: AbortController | null = null;

const projectId = computed(() => {
    const value = route.params.projectId;
    return typeof value === 'string' && /^\d+$/.test(value) ? value : null;
});

const currentProject = computed(() => projects.value.find((project) => String(project.id) === projectId.value) ?? null);

const loadProjects = async (): Promise<void> => {
    projectsController?.abort();
    projects.value = [];
    projectsError.value = '';

    if (!authStore.isAuthenticated) {
        projectsLoading.value = false;
        return;
    }

    const requestController = new AbortController();
    projectsController = requestController;
    projectsLoading.value = true;

    try {
        const loadedProjects = await projectService.list(requestController.signal);
        if (projectsController === requestController) {
            projects.value = loadedProjects;
        }
    } catch (error: unknown) {
        if (projectsController === requestController && !axios.isCancel(error)) {
            projectsError.value = 'Project navigation is unavailable.';
        }
    } finally {
        if (projectsController === requestController) {
            projectsLoading.value = false;
        }
    }
};

const switchProject = async (event: globalThis.Event): Promise<void> => {
    const selectedProjectId = (event.target as globalThis.HTMLSelectElement).value;
    if (!/^\d+$/.test(selectedProjectId) || selectedProjectId === projectId.value) return;

    await router.push(`/projects/${selectedProjectId}`);
};

const openDrawer = async (): Promise<void> => {
    drawerOpen.value = true;
    await nextTick();
    drawer.value?.focus();
};

const closeDrawer = async (): Promise<void> => {
    drawerOpen.value = false;
    await nextTick();
    drawerButton.value?.focus();
};

const handleDrawerKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        void closeDrawer();
        return;
    }
    if (event.key !== 'Tab' || !drawer.value) return;

    const focusable = Array.from(
        drawer.value.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'),
    );
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable.at(-1);
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
    }
};

const signOut = async (): Promise<void> => {
    if (isSigningOut.value) return;
    isSigningOut.value = true;
    signOutError.value = '';

    try {
        await authStore.logout();
        await router.replace('/sign-in');
    } catch {
        if (authStore.isAuthenticated) {
            signOutError.value = 'ToggleFlow could not sign you out. Please try again.';
        }
    } finally {
        isSigningOut.value = false;
    }
};

watch([() => authStore.isAuthenticated, projectId], loadProjects, { immediate: true });
onBeforeUnmount(() => projectsController?.abort());
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6" aria-label="Primary">
                <div class="flex items-center gap-3">
                    <button
                        v-if="authStore.isAuthenticated"
                        ref="drawerButton"
                        class="rounded-lg border border-slate-300 p-2 lg:hidden"
                        type="button"
                        aria-label="Open navigation"
                        :aria-expanded="drawerOpen"
                        @click="openDrawer"
                    >
                        <span aria-hidden="true">☰</span>
                    </button>
                    <RouterLink class="font-semibold text-brand" to="/"> ToggleFlow </RouterLink>
                </div>
                <div v-if="authStore.isAuthenticated" class="flex items-center gap-3">
                    <RouterLink class="rounded px-2 py-2 text-sm font-medium hover:bg-slate-100" to="/app">
                        Dashboard
                    </RouterLink>
                    <RouterLink class="rounded px-2 py-2 text-sm font-medium hover:bg-slate-100" to="/projects">
                        Projects
                    </RouterLink>
                    <details class="relative">
                        <summary
                            class="cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold"
                        >
                            Signed in as {{ authStore.owner?.name }}
                        </summary>
                        <div
                            class="absolute right-0 z-20 mt-2 min-w-48 rounded-xl border border-slate-200 bg-white p-3 shadow-lg"
                        >
                            <p class="text-xs text-slate-500">Signed in</p>
                            <p class="mt-1 truncate text-sm font-medium">{{ authStore.owner?.email }}</p>
                        </div>
                    </details>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-60"
                        type="button"
                        :disabled="isSigningOut"
                        @click="signOut"
                    >
                        {{ isSigningOut ? 'Signing out…' : 'Sign out' }}
                    </button>
                </div>
                <RouterLink v-else class="rounded px-3 py-2 text-sm font-medium" to="/sign-in">Sign in</RouterLink>
            </nav>
            <p v-if="signOutError" class="mx-auto max-w-7xl px-4 pb-3 text-sm text-danger sm:px-6" role="alert">
                {{ signOutError }}
            </p>
        </header>

        <div v-if="authStore.isAuthenticated" class="mx-auto flex max-w-7xl">
            <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white px-4 py-6 lg:block">
                <nav aria-label="Application">
                    <RouterLink class="block rounded-lg px-3 py-2 font-medium hover:bg-slate-100" to="/app">
                        Overview
                    </RouterLink>
                    <RouterLink class="mt-1 block rounded-lg px-3 py-2 font-medium hover:bg-slate-100" to="/projects">
                        Projects
                    </RouterLink>
                    <div class="mt-6 px-3">
                        <label
                            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            for="project-switcher"
                        >
                            Project
                        </label>
                        <p v-if="projectsLoading" class="mt-2 text-sm text-slate-500" role="status">
                            Loading projects…
                        </p>
                        <p v-else-if="projectsError" class="mt-2 text-sm text-danger" role="alert">
                            {{ projectsError }}
                        </p>
                        <select
                            v-else-if="projects.length > 0"
                            id="project-switcher"
                            class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            :value="projectId ?? ''"
                            @change="switchProject"
                        >
                            <option value="" disabled>Select a project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.name }}
                            </option>
                        </select>
                        <p v-else class="mt-2 text-sm text-slate-500">No active projects</p>
                    </div>
                    <template v-if="currentProject">
                        <p class="mt-6 px-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ currentProject.name }}
                        </p>
                        <RouterLink
                            class="mt-2 block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100"
                            :to="`/projects/${projectId}`"
                        >
                            Project overview
                        </RouterLink>
                        <RouterLink
                            class="mt-1 block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100"
                            :to="`/projects/${projectId}/flags`"
                        >
                            Feature flags
                        </RouterLink>
                        <RouterLink
                            class="mt-1 block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-100"
                            :to="`/projects/${projectId}/api-keys`"
                        >
                            API keys
                        </RouterLink>
                    </template>
                </nav>
            </aside>
            <main class="min-w-0 flex-1 px-4 py-8 sm:px-6 sm:py-10">
                <RouterView />
            </main>
        </div>

        <main v-else class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
            <RouterView />
        </main>

        <div v-if="drawerOpen && authStore.isAuthenticated" class="fixed inset-0 z-50 lg:hidden">
            <button
                class="absolute inset-0 bg-slate-950/40"
                type="button"
                aria-label="Close navigation"
                @click="closeDrawer"
            />
            <aside
                ref="drawer"
                class="relative h-full w-80 max-w-[85vw] bg-white p-5 shadow-xl"
                tabindex="-1"
                aria-label="Mobile application navigation"
                @keydown="handleDrawerKeydown"
            >
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-brand">ToggleFlow</span>
                    <button class="rounded-lg border border-slate-300 px-3 py-2" type="button" @click="closeDrawer">
                        Close
                    </button>
                </div>
                <nav class="mt-6" aria-label="Mobile application">
                    <RouterLink class="block rounded-lg px-3 py-2 font-medium" to="/app" @click="closeDrawer">
                        Overview
                    </RouterLink>
                    <RouterLink class="mt-1 block rounded-lg px-3 py-2 font-medium" to="/projects" @click="closeDrawer">
                        Projects
                    </RouterLink>
                    <div class="mt-6 px-3">
                        <label
                            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            for="mobile-project-switcher"
                        >
                            Project
                        </label>
                        <p v-if="projectsLoading" class="mt-2 text-sm text-slate-500" role="status">
                            Loading projects…
                        </p>
                        <p v-else-if="projectsError" class="mt-2 text-sm text-danger" role="alert">
                            {{ projectsError }}
                        </p>
                        <select
                            v-else-if="projects.length > 0"
                            id="mobile-project-switcher"
                            class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            :value="projectId ?? ''"
                            @change="switchProject"
                        >
                            <option value="" disabled>Select a project</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.name }}
                            </option>
                        </select>
                        <p v-else class="mt-2 text-sm text-slate-500">No active projects</p>
                    </div>
                    <template v-if="currentProject">
                        <p class="mt-6 px-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ currentProject.name }}
                        </p>
                        <RouterLink
                            class="mt-2 block rounded-lg px-3 py-2"
                            :to="`/projects/${projectId}`"
                            @click="closeDrawer"
                        >
                            Project overview
                        </RouterLink>
                        <RouterLink
                            class="mt-1 block rounded-lg px-3 py-2"
                            :to="`/projects/${projectId}/flags`"
                            @click="closeDrawer"
                        >
                            Feature flags
                        </RouterLink>
                        <RouterLink
                            class="mt-1 block rounded-lg px-3 py-2"
                            :to="`/projects/${projectId}/api-keys`"
                            @click="closeDrawer"
                        >
                            API keys
                        </RouterLink>
                    </template>
                </nav>
            </aside>
        </div>
    </div>
</template>
