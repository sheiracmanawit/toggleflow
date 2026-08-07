<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

import type { ProjectSummary } from '@features/projects';

interface NavigationItem {
    label: string;
    icon: string;
    to: string;
    exact?: boolean;
}

const props = defineProps<{
    collapsed?: boolean;
    navigationLabel: string;
    currentProject: ProjectSummary | null;
    projectId: string | null;
    projects: ProjectSummary[];
    projectsError: string;
    projectsLoading: boolean;
    switcherId: string;
}>();

const emit = defineEmits<{
    navigate: [];
    switchProject: [projectId: string];
}>();

const workspaceItems: NavigationItem[] = [
    { label: 'Overview', icon: 'i-lucide-layout-dashboard', to: '/app', exact: true },
    { label: 'Projects', icon: 'i-lucide-folder-kanban', to: '/projects', exact: true },
];

const projectItems = computed<NavigationItem[]>(() => {
    if (!props.currentProject || !props.projectId) return [];

    return [
        { label: 'Project overview', icon: 'i-lucide-panel-top', to: `/projects/${props.projectId}`, exact: true },
        { label: 'Feature flags', icon: 'i-lucide-toggle-right', to: `/projects/${props.projectId}/flags` },
        { label: 'API keys', icon: 'i-lucide-key-round', to: `/projects/${props.projectId}/api-keys` },
        { label: 'Audit history', icon: 'i-lucide-history', to: `/projects/${props.projectId}/audit-log` },
    ];
});

const selectProject = (event: globalThis.Event): void => {
    emit('switchProject', (event.target as globalThis.HTMLSelectElement).value);
};
</script>

<template>
    <nav :aria-label="navigationLabel">
        <p class="px-3 text-xs font-semibold uppercase tracking-wide text-text-muted" :class="{ 'sr-only': collapsed }">
            Workspace
        </p>
        <ul class="mt-2 space-y-1">
            <li v-for="item in workspaceItems" :key="item.to">
                <RouterLink v-slot="{ href, isActive, isExactActive, navigate }" :to="item.to" custom>
                    <a
                        :href="href"
                        :aria-current="(item.exact ? isExactActive : isActive) ? 'page' : undefined"
                        :title="collapsed ? item.label : undefined"
                        class="flex items-center gap-3 rounded-lg border-l-2 border-transparent px-3 py-2 text-sm font-medium text-text-muted hover:bg-surface-muted hover:text-text"
                        :class="{
                            'border-brand bg-brand-soft text-text': item.exact ? isExactActive : isActive,
                            'justify-center px-2': collapsed,
                        }"
                        @click="
                            navigate($event);
                            emit('navigate');
                        "
                    >
                        <UIcon :name="item.icon" aria-hidden="true" class="size-4 shrink-0" />
                        <span :class="{ 'sr-only': collapsed }">{{ item.label }}</span>
                        <span v-if="item.exact ? isExactActive : isActive" class="sr-only">(current)</span>
                    </a>
                </RouterLink>
            </li>
        </ul>

        <div class="mt-7 px-3" :class="{ hidden: collapsed }">
            <label class="text-xs font-semibold uppercase tracking-wide text-text-muted" :for="switcherId">
                Project
            </label>
            <p v-if="projectsLoading" class="mt-2 text-sm text-text-muted" role="status">Loading projects…</p>
            <p v-else-if="projectsError" class="mt-2 text-sm text-danger" role="alert">{{ projectsError }}</p>
            <select
                v-else-if="projects.length > 0"
                :id="switcherId"
                class="mt-2 w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text"
                :value="projectId ?? ''"
                @change="selectProject"
            >
                <option value="" disabled>Select a project</option>
                <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
            </select>
            <p v-else class="mt-2 text-sm text-text-muted">No active projects</p>
        </div>

        <template v-if="currentProject">
            <p
                class="mt-7 truncate px-3 text-xs font-semibold uppercase tracking-wide text-text-muted"
                :class="{ 'sr-only': collapsed }"
            >
                {{ currentProject.name }}
            </p>
            <ul class="mt-2 space-y-1">
                <li v-for="item in projectItems" :key="item.to">
                    <RouterLink v-slot="{ href, isActive, isExactActive, navigate }" :to="item.to" custom>
                        <a
                            :href="href"
                            :aria-current="(item.exact ? isExactActive : isActive) ? 'page' : undefined"
                            :title="collapsed ? item.label : undefined"
                            class="flex items-center gap-3 rounded-lg border-l-2 border-transparent px-3 py-2 text-sm font-medium text-text-muted hover:bg-surface-muted hover:text-text"
                            :class="{
                                'border-brand bg-brand-soft text-text': item.exact ? isExactActive : isActive,
                                'justify-center px-2': collapsed,
                            }"
                            @click="
                                navigate($event);
                                emit('navigate');
                            "
                        >
                            <UIcon :name="item.icon" aria-hidden="true" class="size-4 shrink-0" />
                            <span :class="{ 'sr-only': collapsed }">{{ item.label }}</span>
                            <span v-if="item.exact ? isExactActive : isActive" class="sr-only">(current)</span>
                        </a>
                    </RouterLink>
                </li>
            </ul>
        </template>
    </nav>
</template>
