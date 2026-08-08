<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import type { Environment } from '@features/projects';
import type { FeatureFlag } from '../types/featureFlags';
import { useFeatureFlagList } from '../composables/useFeatureFlagList';

const {
    project,
    flags,
    isLoading,
    loadError,
    showCreateForm,
    isSubmitting,
    submitError,
    validationErrors,
    form,
    keyWasEdited,
    stateFor,
    load,
    openCreate,
    submit,
} = useFeatureFlagList();

const pageHeaderTarget = '#page-header-actions';
const environmentColor = (environment: Environment): 'info' | 'neutral' | 'warning' => {
    if (environment.key === 'development') return 'info';
    if (environment.key === 'staging') return 'warning';

    return 'neutral';
};
const productionBadgeClass = (environment: Environment): string | undefined =>
    environment.key === 'production'
        ? 'bg-environment-production/10 text-environment-production ring-environment-production/30'
        : undefined;
const stateLabel = (flag: FeatureFlag, environmentId: number): string => {
    const state = stateFor(flag, environmentId);
    if (!state) return 'Unavailable';

    return state.enabled ? 'Enabled' : 'Disabled';
};
const stateColor = (flag: FeatureFlag, environmentId: number): 'error' | 'neutral' | 'success' => {
    const state = stateFor(flag, environmentId);
    if (!state) return 'error';

    return state.enabled ? 'success' : 'neutral';
};
const stateIcon = (flag: FeatureFlag, environmentId: number): string => {
    const state = stateFor(flag, environmentId);
    if (!state) return 'i-lucide-triangle-alert';

    return state.enabled ? 'i-lucide-circle-check' : 'i-lucide-circle-minus';
};
</script>

<template>
    <section class="-mx-4 -my-6 sm:-mx-6 sm:-my-8" aria-label="Feature flags">
        <Teleport :to="pageHeaderTarget">
            <UButton
                v-if="project?.status === 'active'"
                aria-label="Create flag"
                icon="i-lucide-plus"
                type="button"
                @click="openCreate"
            >
                <span class="hidden sm:inline">Create flag</span>
                <span class="sr-only sm:hidden">Create flag</span>
            </UButton>
        </Teleport>

        <div v-if="isLoading" class="grid gap-3 p-6" role="status" aria-live="polite">
            <p>Loading feature flags…</p>
            <div
                v-for="item in 4"
                :key="item"
                class="h-20 animate-pulse rounded-lg bg-surface-muted"
                aria-hidden="true"
            />
        </div>
        <div v-else-if="loadError" class="m-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <h2 class="text-base font-semibold">Feature flags unavailable</h2>
            <p class="mt-2 text-sm">{{ loadError }}</p>
            <UButton
                class="mt-4"
                color="error"
                icon="i-lucide-refresh-cw"
                type="button"
                variant="outline"
                @click="load"
            >
                Try again
            </UButton>
        </div>
        <template v-else-if="project">
            <div v-if="flags.length === 0" class="p-6" role="status">
                <div class="rounded-lg border border-dashed border-border px-6 py-12 text-center">
                    <UIcon name="i-lucide-toggle-right" aria-hidden="true" class="mx-auto size-7 text-text-muted" />
                    <h2 class="mt-3 text-base font-semibold">Create your first feature flag</h2>
                    <p class="mx-auto mt-1 max-w-xl text-sm text-text-muted">
                        A boolean flag is a stable decision point. New flags begin disabled in Development, Staging, and
                        Production.
                    </p>
                    <UButton
                        v-if="project.status === 'active'"
                        class="mt-5"
                        icon="i-lucide-plus"
                        type="button"
                        @click="openCreate"
                    >
                        Create flag
                    </UButton>
                </div>
            </div>

            <template v-else>
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6">
                    <div>
                        <p class="text-sm font-semibold text-text">Release state</p>
                        <p class="mt-0.5 hidden text-sm text-text-muted sm:block">
                            Compare every flag across project environments
                        </p>
                    </div>
                    <UBadge color="neutral" icon="i-lucide-toggle-right" variant="subtle">
                        {{ flags.length }} {{ flags.length === 1 ? 'flag' : 'flags' }}
                    </UBadge>
                </div>

                <section class="px-4 pb-6 sm:px-6" aria-label="Feature flag inventory">
                    <div class="hidden overflow-x-auto rounded-lg border border-border xl:block">
                        <table class="min-w-[58rem] w-full border-collapse text-left text-sm">
                            <thead class="border-b border-border bg-surface-muted text-text">
                                <tr>
                                    <th class="px-5 py-3.5 font-semibold" scope="col">Flag</th>
                                    <th class="px-5 py-3.5 font-semibold" scope="col">Lifecycle</th>
                                    <th
                                        v-for="environment in project.environments"
                                        :key="environment.id"
                                        class="px-5 py-3.5 font-semibold"
                                        scope="col"
                                    >
                                        <UBadge
                                            :class="productionBadgeClass(environment)"
                                            :color="environmentColor(environment)"
                                            variant="subtle"
                                        >
                                            {{ environment.name }}
                                        </UBadge>
                                    </th>
                                    <th class="w-24 px-5 py-3.5 text-right font-semibold" scope="col">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="flag in flags" :key="flag.id" class="hover:bg-surface-muted/50">
                                    <th class="px-5 py-4 align-middle font-normal" scope="row">
                                        <RouterLink
                                            class="font-semibold text-text hover:text-brand hover:underline"
                                            :to="`/projects/${project.id}/flags/${flag.id}`"
                                        >
                                            {{ flag.name }}
                                        </RouterLink>
                                        <span class="mt-0.5 block font-mono text-xs text-text-muted">{{
                                            flag.key
                                        }}</span>
                                        <span
                                            v-if="flag.description"
                                            class="mt-1.5 block max-w-sm text-sm text-text-muted"
                                        >
                                            {{ flag.description }}
                                        </span>
                                    </th>
                                    <td class="px-5 py-4 align-middle">
                                        <UBadge
                                            :color="flag.status === 'active' ? 'primary' : 'neutral'"
                                            :icon="
                                                flag.status === 'active' ? 'i-lucide-circle-dot' : 'i-lucide-archive'
                                            "
                                            variant="subtle"
                                        >
                                            {{ flag.status === 'active' ? 'Active' : 'Archived' }}
                                        </UBadge>
                                    </td>
                                    <td
                                        v-for="environment in project.environments"
                                        :key="environment.id"
                                        class="px-5 py-4 align-middle"
                                    >
                                        <UBadge
                                            :color="stateColor(flag, environment.id)"
                                            :icon="stateIcon(flag, environment.id)"
                                            variant="subtle"
                                        >
                                            {{ stateLabel(flag, environment.id) }}
                                        </UBadge>
                                    </td>
                                    <td class="px-5 py-4 text-right align-middle">
                                        <RouterLink
                                            :aria-label="`Manage ${flag.name}`"
                                            class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-semibold text-brand hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                            :to="`/projects/${project.id}/flags/${flag.id}`"
                                        >
                                            Manage
                                            <UIcon name="i-lucide-arrow-right" aria-hidden="true" class="size-4" />
                                        </RouterLink>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <ul class="divide-y divide-border overflow-hidden rounded-lg border border-border xl:hidden">
                        <li v-for="flag in flags" :key="flag.id" class="p-4 sm:p-5">
                            <article>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <RouterLink
                                            class="font-semibold text-text hover:text-brand hover:underline"
                                            :to="`/projects/${project.id}/flags/${flag.id}`"
                                        >
                                            {{ flag.name }}
                                        </RouterLink>
                                        <p class="mt-0.5 break-all font-mono text-xs text-text-muted">{{ flag.key }}</p>
                                    </div>
                                    <UBadge
                                        :color="flag.status === 'active' ? 'primary' : 'neutral'"
                                        :icon="flag.status === 'active' ? 'i-lucide-circle-dot' : 'i-lucide-archive'"
                                        size="sm"
                                        variant="subtle"
                                    >
                                        {{ flag.status === 'active' ? 'Active' : 'Archived' }}
                                    </UBadge>
                                </div>
                                <p v-if="flag.description" class="mt-3 text-sm text-text-muted">
                                    {{ flag.description }}
                                </p>
                                <dl class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                                    <div v-for="environment in project.environments" :key="environment.id">
                                        <dt>
                                            <UBadge
                                                :class="productionBadgeClass(environment)"
                                                :color="environmentColor(environment)"
                                                size="sm"
                                                variant="subtle"
                                            >
                                                {{ environment.name }}
                                            </UBadge>
                                        </dt>
                                        <dd class="mt-1.5">
                                            <UBadge
                                                :color="stateColor(flag, environment.id)"
                                                :icon="stateIcon(flag, environment.id)"
                                                size="sm"
                                                variant="subtle"
                                            >
                                                {{ stateLabel(flag, environment.id) }}
                                            </UBadge>
                                        </dd>
                                    </div>
                                </dl>
                                <div class="mt-4 flex justify-end border-t border-border pt-3">
                                    <RouterLink
                                        :aria-label="`Manage ${flag.name}`"
                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-semibold text-brand hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                        :to="`/projects/${project.id}/flags/${flag.id}`"
                                    >
                                        Manage
                                        <UIcon name="i-lucide-arrow-right" aria-hidden="true" class="size-4" />
                                    </RouterLink>
                                </div>
                            </article>
                        </li>
                    </ul>
                </section>
            </template>

            <AppDialog
                v-if="showCreateForm"
                title="Create a boolean flag"
                description="It will begin disabled in Development, Staging, and Production."
                @cancel="!isSubmitting && (showCreateForm = false)"
            >
                <form class="grid gap-5" novalidate @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-semibold" for="flag-name">Display name</label>
                        <input
                            id="flag-name"
                            v-model="form.name"
                            class="mt-1 w-full rounded-lg border border-border bg-surface px-3 py-2"
                            :aria-describedby="validationErrors.name ? 'flag-name-error' : undefined"
                            :aria-invalid="Boolean(validationErrors.name)"
                        />
                        <p v-if="validationErrors.name" id="flag-name-error" class="mt-1 text-sm text-danger">
                            {{ validationErrors.name[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold" for="flag-key">Machine-readable key</label>
                        <input
                            id="flag-key"
                            v-model="form.key"
                            class="mt-1 w-full rounded-lg border border-border bg-surface px-3 py-2 font-mono"
                            autocomplete="off"
                            :aria-describedby="validationErrors.key ? 'flag-key-error' : 'flag-key-help'"
                            :aria-invalid="Boolean(validationErrors.key)"
                            @input="keyWasEdited = true"
                        />
                        <p id="flag-key-help" class="mt-1 text-xs text-text-muted">
                            Lowercase letters, numbers, and hyphens. The key cannot be changed after creation.
                        </p>
                        <p v-if="validationErrors.key" id="flag-key-error" class="mt-1 text-sm text-danger">
                            {{ validationErrors.key[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold" for="flag-description">Description (optional)</label>
                        <textarea
                            id="flag-description"
                            v-model="form.description"
                            class="mt-1 min-h-24 w-full rounded-lg border border-border bg-surface px-3 py-2"
                            :aria-describedby="validationErrors.description ? 'flag-description-error' : undefined"
                            :aria-invalid="Boolean(validationErrors.description)"
                        />
                        <p
                            v-if="validationErrors.description"
                            id="flag-description-error"
                            class="mt-1 text-sm text-danger"
                        >
                            {{ validationErrors.description[0] }}
                        </p>
                    </div>
                    <p v-if="submitError" class="text-sm text-danger" role="alert">{{ submitError }}</p>
                    <div class="flex justify-end gap-3">
                        <UButton
                            color="neutral"
                            type="button"
                            variant="ghost"
                            :disabled="isSubmitting"
                            @click="showCreateForm = false"
                        >
                            Cancel
                        </UButton>
                        <UButton type="submit" :loading="isSubmitting" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Creating flag…' : 'Create flag' }}
                        </UButton>
                    </div>
                </form>
            </AppDialog>
        </template>
    </section>
</template>
