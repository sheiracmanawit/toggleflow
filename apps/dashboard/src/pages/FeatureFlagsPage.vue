<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import { FeatureFlagValidationError, featureFlagService, projectService } from '../services';
import type { EnvironmentFlagState, FeatureFlag, Project, ValidationErrors } from '../types';

const route = useRoute();
const router = useRouter();
const project = ref<Project | null>(null);
const flags = ref<FeatureFlag[]>([]);
const isLoading = ref(true);
const loadError = ref('');
const showCreateForm = ref(false);
const isSubmitting = ref(false);
const submitError = ref('');
const validationErrors = ref<ValidationErrors>({});
const form = reactive({ name: '', key: '', description: '' });
const keyWasEdited = ref(false);
let controller: AbortController | null = null;

const stateFor = (flag: FeatureFlag, environmentId: number): EnvironmentFlagState | undefined =>
    flag.environment_states.find((state) => state.environment.id === environmentId);

const suggestedKey = computed(() =>
    form.name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, ''),
);

const load = async (): Promise<void> => {
    controller?.abort();
    controller = new AbortController();
    const projectId = Number(route.params.projectId);
    isLoading.value = true;
    loadError.value = '';
    project.value = null;
    flags.value = [];
    try {
        const [loadedProject, loadedFlags] = await Promise.all([
            projectService.get(projectId, controller.signal),
            featureFlagService.list(projectId, controller.signal),
        ]);
        if (Number(route.params.projectId) === projectId) {
            project.value = loadedProject;
            flags.value = loadedFlags;
        }
    } catch (error: unknown) {
        if (!axios.isCancel(error)) {
            loadError.value = 'Feature flags could not be loaded or you do not have access to this project.';
        }
    } finally {
        if (Number(route.params.projectId) === projectId) isLoading.value = false;
    }
};

const openCreate = (): void => {
    showCreateForm.value = true;
    submitError.value = '';
    validationErrors.value = {};
};

const submit = async (): Promise<void> => {
    if (isSubmitting.value) return;
    const projectId = Number(route.params.projectId);
    isSubmitting.value = true;
    submitError.value = '';
    validationErrors.value = {};
    try {
        const created = await featureFlagService.create(projectId, {
            name: form.name,
            key: form.key || suggestedKey.value,
            description: form.description,
        });
        await router.push(`/projects/${projectId}/flags/${created.id}`);
    } catch (error: unknown) {
        if (error instanceof FeatureFlagValidationError) validationErrors.value = error.errors;
        else submitError.value = 'The flag was not created. Your entered information has been preserved.';
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    () => form.name,
    () => {
        if (!keyWasEdited.value) form.key = suggestedKey.value;
    },
);
watch(() => route.params.projectId, load, { immediate: true });
onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <section aria-labelledby="flags-heading">
        <RouterLink
            class="text-sm font-semibold text-brand hover:underline"
            :to="`/projects/${route.params.projectId}`"
        >
            ← Project overview
        </RouterLink>
        <p v-if="isLoading" class="mt-8 rounded-xl border border-slate-200 bg-white p-6" role="status">
            Loading feature flags…
        </p>
        <div v-else-if="loadError" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h1 id="flags-heading" class="text-xl font-semibold">Feature flags unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand">{{ project.name }}</p>
                    <h1 id="flags-heading" class="mt-1 text-3xl font-bold">Feature flags</h1>
                    <p class="mt-2 text-slate-600">Control application behavior independently in each environment.</p>
                </div>
                <button
                    class="self-start rounded-lg bg-brand px-4 py-2 font-semibold text-white"
                    type="button"
                    @click="openCreate"
                >
                    Create flag
                </button>
            </div>

            <div
                v-if="flags.length === 0"
                class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"
            >
                <h2 class="text-xl font-semibold">Create your first feature flag</h2>
                <p class="mx-auto mt-2 max-w-xl text-slate-600">
                    A boolean flag is a stable decision point. New flags begin disabled in Development, Staging, and
                    Production.
                </p>
                <button
                    class="mt-5 rounded-lg bg-brand px-4 py-2 font-semibold text-white"
                    type="button"
                    @click="openCreate"
                >
                    Create flag
                </button>
            </div>

            <div v-else class="mt-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="hidden w-full text-left md:table">
                    <thead class="border-b border-slate-200 bg-slate-50 text-sm">
                        <tr>
                            <th class="px-5 py-3">Flag</th>
                            <th v-for="environment in project.environments" :key="environment.id" class="px-5 py-3">
                                {{ environment.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="flag in flags" :key="flag.id" class="border-b border-slate-100 last:border-0">
                            <td class="px-5 py-4">
                                <RouterLink
                                    class="font-semibold text-brand hover:underline"
                                    :to="`/projects/${project.id}/flags/${flag.id}`"
                                >
                                    {{ flag.name }}
                                </RouterLink>
                                <p class="mt-1 font-mono text-sm text-slate-500">{{ flag.key }}</p>
                            </td>
                            <td v-for="environment in project.environments" :key="environment.id" class="px-5 py-4">
                                <span
                                    v-if="stateFor(flag, environment.id)"
                                    class="font-semibold"
                                    :class="stateFor(flag, environment.id)?.enabled ? 'text-enabled' : 'text-disabled'"
                                >
                                    {{ stateFor(flag, environment.id)?.enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                                <span v-else class="text-sm font-semibold text-danger">Unavailable</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <ul class="grid gap-4 p-4 md:hidden">
                    <li v-for="flag in flags" :key="flag.id" class="rounded-xl border border-slate-200 p-4">
                        <RouterLink
                            class="font-semibold text-brand hover:underline"
                            :to="`/projects/${project.id}/flags/${flag.id}`"
                        >
                            {{ flag.name }}
                        </RouterLink>
                        <p class="mt-1 break-all font-mono text-sm text-slate-500">{{ flag.key }}</p>
                        <dl class="mt-4 grid gap-2">
                            <div
                                v-for="state in flag.environment_states"
                                :key="state.environment.id"
                                class="flex justify-between gap-4"
                            >
                                <dt>{{ state.environment.name }}</dt>
                                <dd class="font-semibold" :class="state.enabled ? 'text-enabled' : 'text-disabled'">
                                    {{ state.enabled ? 'Enabled' : 'Disabled' }}
                                </dd>
                            </div>
                        </dl>
                    </li>
                </ul>
            </div>

            <form
                v-if="showCreateForm"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-6"
                novalidate
                @submit.prevent="submit"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Create a boolean flag</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            It will begin disabled in Development, Staging, and Production.
                        </p>
                    </div>
                    <button type="button" :disabled="isSubmitting" @click="showCreateForm = false">Close</button>
                </div>
                <div class="mt-6 grid gap-5">
                    <div>
                        <label class="block text-sm font-semibold" for="flag-name">Display name</label>
                        <input
                            id="flag-name"
                            v-model="form.name"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono"
                            autocomplete="off"
                            :aria-describedby="validationErrors.key ? 'flag-key-error' : 'flag-key-help'"
                            :aria-invalid="Boolean(validationErrors.key)"
                            @input="keyWasEdited = true"
                        />
                        <p id="flag-key-help" class="mt-1 text-xs text-slate-500">
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
                            class="mt-1 min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2"
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
                    <div class="flex gap-3">
                        <button
                            class="rounded-lg bg-brand px-4 py-2 font-semibold text-white disabled:opacity-60"
                            type="submit"
                            :disabled="isSubmitting"
                        >
                            {{ isSubmitting ? 'Creating flag…' : 'Create flag' }}
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
                </div>
            </form>
        </template>
    </section>
</template>
