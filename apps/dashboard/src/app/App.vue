<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';

import { useAuthStore } from '@features/authentication';
import { useProjectContextStore } from '@features/projects';
import ApplicationNavigation from './components/ApplicationNavigation.vue';
import ThemePreferenceSelector from './components/ThemePreferenceSelector.vue';
import { useNavigationDrawer } from './composables/useNavigationDrawer';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const projectContextStore = useProjectContextStore();
const { projects, isLoading: projectsLoading, error: projectsError } = storeToRefs(projectContextStore);
const { drawerOpen, drawer, drawerButton, drawerCloseButton, openDrawer, closeDrawer, handleDrawerKeydown } =
    useNavigationDrawer();
const isSigningOut = ref(false);
const signOutError = ref('');
const sidebarCollapsed = ref(false);
const userMenu = ref<globalThis.HTMLDetailsElement | null>(null);
const userMenuButton = ref<HTMLElement | null>(null);

const projectId = computed(() => {
    const value = route.params.projectId;
    return typeof value === 'string' && /^\d+$/.test(value) ? value : null;
});
const currentProject = computed(() => projects.value.find((project) => String(project.id) === projectId.value) ?? null);
const currentSection = computed(() => {
    if (route.path === '/app') return 'Overview';
    if (route.path === '/projects') return 'Projects';
    if (route.path.endsWith('/flags')) return 'Feature flags';
    if (route.path.includes('/flags/')) return 'Feature flag';
    if (route.path.endsWith('/api-keys')) return 'API keys';
    if (route.path.endsWith('/audit-log')) return 'Audit history';
    if (projectId.value) return 'Project overview';
    return 'ToggleFlow';
});
const switchProject = async (selectedProjectId: string): Promise<void> => {
    if (!/^\d+$/.test(selectedProjectId) || selectedProjectId === projectId.value) return;
    await router.push(`/projects/${selectedProjectId}`);
    if (drawerOpen.value) await closeDrawer();
};

const signOut = async (): Promise<void> => {
    if (isSigningOut.value) return;
    isSigningOut.value = true;
    signOutError.value = '';
    try {
        await authStore.logout();
        await router.replace('/sign-in');
    } catch {
        if (authStore.isAuthenticated) signOutError.value = 'ToggleFlow could not sign you out. Please try again.';
    } finally {
        isSigningOut.value = false;
    }
};

const closeUserMenu = (restoreFocus = false): void => {
    if (!userMenu.value?.open) return;
    userMenu.value.open = false;
    if (restoreFocus) userMenuButton.value?.focus();
};

const handleUserMenuKeydown = (event: globalThis.KeyboardEvent): void => {
    if (event.key === 'Escape' && userMenu.value?.open) {
        event.preventDefault();
        closeUserMenu(true);
    }
};

const handleUserMenuPointerdown = (event: globalThis.PointerEvent): void => {
    if (userMenu.value?.open && !userMenu.value.contains(event.target as globalThis.Node)) closeUserMenu();
};

watch([() => authStore.isAuthenticated, projectId], () => projectContextStore.load(authStore.isAuthenticated), {
    immediate: true,
});
onMounted(() => {
    document.addEventListener('keydown', handleUserMenuKeydown);
    document.addEventListener('pointerdown', handleUserMenuPointerdown);
});
onBeforeUnmount(() => {
    projectContextStore.cancel();
    document.removeEventListener('keydown', handleUserMenuKeydown);
    document.removeEventListener('pointerdown', handleUserMenuPointerdown);
});
</script>

<template>
    <UApp :toaster="{ position: 'top-right' }">
        <div class="min-h-screen bg-page">
            <div v-if="authStore.isAuthenticated" class="flex min-h-screen w-full" :inert="drawerOpen">
                <aside
                    class="sticky top-0 hidden h-screen shrink-0 flex-col border-r border-border bg-surface transition-[width] duration-200 md:flex"
                    :class="sidebarCollapsed ? 'w-16' : 'w-64'"
                >
                    <div
                        class="flex h-16 items-center border-b border-border"
                        :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-5'"
                    >
                        <RouterLink class="flex items-center gap-2 font-semibold text-text" to="/app">
                            <span
                                class="flex size-8 items-center justify-center rounded-lg bg-brand text-sm font-bold text-on-brand"
                                >T</span
                            >
                            <span v-if="!sidebarCollapsed">ToggleFlow</span>
                            <span v-else class="sr-only">ToggleFlow</span>
                        </RouterLink>
                        <UIcon
                            v-if="!sidebarCollapsed"
                            name="i-lucide-chevrons-up-down"
                            aria-hidden="true"
                            class="size-4 text-text-muted"
                        />
                    </div>
                    <ApplicationNavigation
                        navigation-label="Application"
                        class="min-h-0 flex-1 overflow-y-auto px-4 py-5"
                        :collapsed="sidebarCollapsed"
                        :current-project="currentProject"
                        :project-id="projectId"
                        :projects="projects"
                        :projects-error="projectsError"
                        :projects-loading="projectsLoading"
                        switcher-id="project-switcher"
                        @switch-project="switchProject"
                    />
                    <div class="border-t border-border p-3">
                        <details ref="userMenu" class="relative">
                            <summary
                                ref="userMenuButton"
                                :aria-label="`Open user menu for ${authStore.owner?.name ?? 'account'}`"
                                class="flex cursor-pointer list-none items-center gap-3 rounded-lg px-2 py-2 hover:bg-surface-muted"
                                :class="{ 'justify-center': sidebarCollapsed }"
                            >
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-sm font-semibold text-brand"
                                >
                                    {{ authStore.owner?.name?.charAt(0) }}
                                </span>
                                <div v-if="!sidebarCollapsed" class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-text">{{ authStore.owner?.name }}</p>
                                    <p class="truncate text-xs text-text-muted">{{ authStore.owner?.email }}</p>
                                </div>
                                <UIcon
                                    v-if="!sidebarCollapsed"
                                    name="i-lucide-chevrons-up-down"
                                    aria-hidden="true"
                                    class="size-4 text-text-muted"
                                />
                            </summary>
                            <div
                                class="absolute bottom-full z-40 mb-2 w-64 rounded-xl border border-border bg-surface-elevated p-2 shadow-elevated"
                                :class="sidebarCollapsed ? 'left-11' : 'left-0'"
                            >
                                <div class="border-b border-border px-3 py-2">
                                    <p class="truncate text-sm font-semibold">{{ authStore.owner?.name }}</p>
                                    <p class="mt-1 truncate text-xs text-text-muted">{{ authStore.owner?.email }}</p>
                                </div>
                                <button
                                    class="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium hover:bg-surface-muted disabled:opacity-60"
                                    data-testid="desktop-sign-out"
                                    type="button"
                                    :disabled="isSigningOut"
                                    @click="signOut"
                                >
                                    <UIcon name="i-lucide-log-out" aria-hidden="true" class="size-4" />
                                    {{ isSigningOut ? 'Signing out…' : 'Sign out' }}
                                </button>
                            </div>
                        </details>
                    </div>
                </aside>

                <div class="min-w-0 flex-1">
                    <header class="sticky top-0 z-30 border-b border-border bg-surface" :inert="drawerOpen">
                        <nav class="flex h-16 w-full items-center justify-between px-4 sm:px-6" aria-label="Primary">
                            <div class="flex min-w-0 items-center gap-3">
                                <button
                                    ref="drawerButton"
                                    aria-label="Open navigation"
                                    class="rounded-lg p-2 text-text-muted hover:bg-surface-muted hover:text-text md:hidden"
                                    type="button"
                                    :aria-expanded="drawerOpen"
                                    @click="openDrawer"
                                    @keydown.enter.prevent="openDrawer"
                                    @keydown.space.prevent="openDrawer"
                                >
                                    <UIcon name="i-lucide-menu" aria-hidden="true" class="block size-5" />
                                </button>
                                <RouterLink class="font-semibold text-brand md:hidden" to="/app">ToggleFlow</RouterLink>
                                <div class="hidden min-w-0 items-center gap-3 md:flex">
                                    <button
                                        class="flex size-9 items-center justify-center rounded-lg text-text-muted hover:bg-surface-muted hover:text-text"
                                        type="button"
                                        :aria-expanded="!sidebarCollapsed"
                                        :aria-label="sidebarCollapsed ? 'Show sidebar' : 'Hide sidebar'"
                                        @click="sidebarCollapsed = !sidebarCollapsed"
                                    >
                                        <UIcon
                                            :name="
                                                sidebarCollapsed
                                                    ? 'i-lucide-panel-left-open'
                                                    : 'i-lucide-panel-left-close'
                                            "
                                            aria-hidden="true"
                                            class="size-5"
                                        />
                                    </button>
                                    <div class="min-w-0">
                                        <h1 class="truncate text-sm font-semibold text-text">{{ currentSection }}</h1>
                                        <p v-if="currentProject" class="truncate text-xs text-text-muted">
                                            {{ currentProject.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <div id="page-header-actions" class="flex items-center gap-2" />
                                <ThemePreferenceSelector />
                            </div>
                        </nav>
                        <p v-if="signOutError" class="px-4 pb-3 text-sm text-danger sm:px-6" role="alert">
                            {{ signOutError }}
                        </p>
                    </header>
                    <main class="min-w-0 px-4 py-6 sm:px-6 sm:py-8"><RouterView /></main>
                </div>
            </div>

            <template v-else>
                <header class="sticky top-0 z-30 border-b border-border bg-surface">
                    <nav class="flex h-16 w-full items-center justify-between px-4 sm:px-6" aria-label="Primary">
                        <RouterLink class="font-semibold text-brand" to="/">ToggleFlow</RouterLink>
                        <div class="flex items-center gap-2">
                            <ThemePreferenceSelector />
                            <RouterLink class="rounded px-3 py-2 text-sm font-medium" to="/sign-in">Sign in</RouterLink>
                        </div>
                    </nav>
                </header>
                <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12"><RouterView /></main>
            </template>

            <div v-if="drawerOpen && authStore.isAuthenticated" class="fixed inset-0 z-50 md:hidden">
                <button
                    class="absolute inset-0 bg-slate-950/50"
                    type="button"
                    aria-label="Close navigation"
                    @click="closeDrawer"
                />
                <aside
                    ref="drawer"
                    aria-label="Mobile application navigation"
                    aria-modal="true"
                    class="relative h-full w-80 max-w-[85vw] overflow-y-auto bg-surface p-5 shadow-xl"
                    role="dialog"
                    @keydown="handleDrawerKeydown"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-brand">ToggleFlow</p>
                            <p v-if="currentProject" class="truncate text-sm text-text-muted">
                                {{ currentProject.name }}
                            </p>
                        </div>
                        <button
                            ref="drawerCloseButton"
                            aria-label="Close navigation"
                            class="rounded-lg p-2 text-text-muted hover:bg-surface-muted hover:text-text"
                            type="button"
                            @click="closeDrawer"
                        >
                            <UIcon name="i-lucide-x" aria-hidden="true" class="block size-5" />
                        </button>
                    </div>
                    <ApplicationNavigation
                        navigation-label="Mobile application"
                        class="mt-6"
                        :current-project="currentProject"
                        :project-id="projectId"
                        :projects="projects"
                        :projects-error="projectsError"
                        :projects-loading="projectsLoading"
                        switcher-id="mobile-project-switcher"
                        @navigate="closeDrawer"
                        @switch-project="switchProject"
                    />
                    <div class="mt-8 border-t border-border px-3 pt-5">
                        <p class="truncate text-sm font-semibold">{{ authStore.owner?.name }}</p>
                        <p class="mt-1 break-all text-xs text-text-muted">{{ authStore.owner?.email }}</p>
                        <button
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-border px-3 py-2 text-sm font-semibold hover:bg-surface-muted disabled:opacity-60"
                            type="button"
                            :disabled="isSigningOut"
                            @click="signOut"
                        >
                            <UIcon name="i-lucide-log-out" aria-hidden="true" class="size-4" />
                            {{ isSigningOut ? 'Signing out…' : 'Sign out' }}
                        </button>
                        <p v-if="signOutError" class="mt-3 text-sm text-danger" role="alert">{{ signOutError }}</p>
                    </div>
                </aside>
            </div>
        </div>
    </UApp>
</template>
