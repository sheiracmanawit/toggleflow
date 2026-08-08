<script setup lang="ts">
import { computed, ref } from 'vue';

import { AppDialog } from '@shared/ui';
import { useApiKeyLifecycle } from '../composables/useApiKeyLifecycle';
import type { ApiKey } from '../types/apiKeys';

const {
    project,
    apiKeys,
    isLoading,
    loadError,
    showCreate,
    isSubmitting,
    mutationError,
    successMessage,
    validationErrors,
    form,
    issuedCredential,
    issuedKey,
    acknowledged,
    copyMessage,
    keyToRevoke,
    isRevoking,
    environmentExample,
    load,
    issue,
    copyCredential,
    dismissIssued,
    revoke,
} = useApiKeyLifecycle();

const query = ref('');
const environmentFilter = ref('all');
const pageHeaderTarget = '#page-header-actions';
const environmentOptions = computed(() => [
    { label: 'All environments', value: 'all' },
    ...(project.value?.environments.map((environment) => ({
        label: environment.name,
        value: environment.key,
    })) ?? []),
]);
const filteredKeys = computed(() => {
    const normalizedQuery = query.value.trim().toLowerCase();

    return apiKeys.value.filter(
        (apiKey) =>
            (environmentFilter.value === 'all' || apiKey.environment.key === environmentFilter.value) &&
            (!normalizedQuery ||
                apiKey.name.toLowerCase().includes(normalizedQuery) ||
                apiKey.prefix.toLowerCase().includes(normalizedQuery)),
    );
});
const openRevoke = (apiKey: ApiKey): void => {
    mutationError.value = '';
    keyToRevoke.value = apiKey;
};
</script>

<template>
    <section class="-mx-4 -my-6 sm:-mx-6 sm:-my-8" aria-label="API keys">
        <Teleport :to="pageHeaderTarget">
            <UButton v-if="project?.status === 'active'" icon="i-lucide-plus" type="button" @click="showCreate = true">
                Issue API key
            </UButton>
        </Teleport>
        <p v-if="isLoading" class="p-6" role="status">Loading API keys…</p>
        <div v-else-if="loadError" class="m-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <h1 class="text-base font-semibold">API keys unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <UDashboardToolbar class="px-4 py-5 sm:px-6">
                <template #left>
                    <UInput
                        v-model="query"
                        aria-label="Filter API keys"
                        class="w-full sm:w-72"
                        icon="i-lucide-search"
                        placeholder="Filter credentials…"
                    />
                </template>
                <template #right>
                    <select
                        v-model="environmentFilter"
                        aria-label="Filter by environment"
                        class="h-9 w-44 rounded-md border border-border bg-surface px-3 text-sm text-text shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    >
                        <option v-for="option in environmentOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </template>
            </UDashboardToolbar>
            <p
                v-if="successMessage"
                class="border-b border-border px-4 py-3 text-sm font-semibold text-enabled sm:px-6"
                role="status"
            >
                {{ successMessage }}
            </p>
            <p
                v-if="mutationError"
                class="border-b border-border px-4 py-3 text-sm font-semibold text-danger sm:px-6"
                role="alert"
            >
                {{ mutationError }}
            </p>
            <p
                v-if="project.status === 'archived'"
                class="border-b border-border px-4 py-3 text-sm text-text-muted sm:px-6"
            >
                This project is archived. Credential metadata remains available for reference, but credentials cannot be
                issued or revoked.
            </p>

            <section class="px-4 pb-6 sm:px-6" aria-label="API key inventory">
                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="min-w-[46rem] w-full border-collapse text-left text-sm">
                        <thead class="border-b border-border bg-surface-muted text-sm text-text">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Environment</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Credential</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Status</th>
                                <th class="px-5 py-3.5 font-semibold" scope="col">Activity</th>
                                <th class="w-14 px-5 py-3.5 text-right font-semibold" scope="col">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="apiKey in filteredKeys" :key="apiKey.id">
                                <th class="px-5 py-4 align-middle font-semibold" scope="row">
                                    <UBadge
                                        :color="
                                            apiKey.environment.key === 'development'
                                                ? 'info'
                                                : apiKey.environment.key === 'staging'
                                                  ? 'warning'
                                                  : 'secondary'
                                        "
                                        variant="subtle"
                                    >
                                        {{ apiKey.environment.name }}
                                    </UBadge>
                                </th>
                                <td class="px-5 py-4 align-middle">
                                    <span class="block font-semibold">{{ apiKey.name }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-text-muted"
                                        >tf_env_{{ apiKey.prefix }}_…</span
                                    >
                                </td>
                                <td class="px-5 py-4 align-middle">
                                    <UBadge :color="apiKey.state === 'active' ? 'success' : 'neutral'" variant="subtle">
                                        {{ apiKey.state === 'active' ? 'Active' : 'Revoked' }}
                                    </UBadge>
                                </td>
                                <td class="px-5 py-4 align-middle text-sm text-text-muted">
                                    <span class="block"
                                        >Created {{ new Date(apiKey.created_at).toLocaleString() }}</span
                                    >
                                    <span v-if="apiKey.last_used_at" class="block">
                                        Last used {{ new Date(apiKey.last_used_at).toLocaleString() }}
                                    </span>
                                    <span v-else class="block">Never used</span>
                                </td>
                                <td class="px-5 py-4 text-right align-middle">
                                    <UButton
                                        v-if="project.status === 'active' && apiKey.state === 'active'"
                                        :aria-label="`Revoke ${apiKey.name}`"
                                        color="neutral"
                                        icon="i-lucide-ellipsis-vertical"
                                        size="sm"
                                        type="button"
                                        variant="ghost"
                                        @click="openRevoke(apiKey)"
                                    />
                                    <span v-else class="text-xs text-text-muted">—</span>
                                </td>
                            </tr>
                            <tr v-if="filteredKeys.length === 0">
                                <td class="px-5 py-10 text-center text-text-muted" colspan="5">
                                    No credentials match the current filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </section>

    <AppDialog
        v-if="showCreate && project"
        title="Issue an environment API key"
        description="Choose one project environment. The complete credential will be shown only once after issuance."
        @cancel="!isSubmitting && (showCreate = false)"
    >
        <form class="grid gap-5" novalidate @submit.prevent="issue">
            <div>
                <label class="block text-sm font-semibold" for="api-key-name">Name</label>
                <input
                    id="api-key-name"
                    v-model="form.name"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                    :aria-invalid="Boolean(validationErrors.name)"
                    :aria-describedby="validationErrors.name ? 'api-key-name-error' : undefined"
                />
                <p v-if="validationErrors.name" id="api-key-name-error" class="mt-1 text-sm text-danger">
                    {{ validationErrors.name[0] }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold" for="api-key-environment">Environment</label>
                <select
                    id="api-key-environment"
                    v-model.number="form.environmentId"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                >
                    <option v-for="environment in project.environments" :key="environment.id" :value="environment.id">
                        {{ environment.name }}
                    </option>
                </select>
                <p
                    v-if="project.environments.find((item) => item.id === form.environmentId)?.key === 'production'"
                    class="mt-2 text-sm text-environment-production"
                >
                    Production key — applications using this credential will evaluate Production release state.
                </p>
            </div>
            <p v-if="mutationError" class="text-sm text-danger" role="alert">{{ mutationError }}</p>
            <div class="flex justify-end gap-3">
                <button type="button" :disabled="isSubmitting" @click="showCreate = false">Cancel</button>
                <button
                    class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand disabled:opacity-60"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? 'Issuing…' : 'Issue API key' }}
                </button>
            </div>
        </form>
    </AppDialog>

    <AppDialog
        v-if="issuedKey && issuedCredential"
        title="Store this API key now"
        description="This complete credential is shown once and cannot be retrieved later."
        @cancel="dismissIssued"
    >
        <p class="text-sm font-semibold">{{ issuedKey.name }} · {{ issuedKey.environment.name }}</p>
        <code class="mt-3 block max-h-32 overflow-auto break-all rounded-lg bg-slate-950 p-3 text-sm text-white">
            {{ issuedCredential }}
        </code>
        <button
            class="mt-4 rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand"
            type="button"
            @click="copyCredential"
        >
            Copy API key
        </button>
        <p v-if="copyMessage" class="mt-2 text-sm" role="status">{{ copyMessage }}</p>
        <code class="mt-4 block overflow-x-auto rounded-lg bg-slate-100 p-3 text-sm">{{ environmentExample }}</code>
        <label class="mt-5 flex items-start gap-3 text-sm">
            <input v-model="acknowledged" class="mt-1" type="checkbox" />
            I stored this credential securely and understand it cannot be viewed again.
        </label>
        <button
            class="mt-5 rounded-lg border border-slate-300 px-4 py-2 font-semibold disabled:opacity-50"
            type="button"
            :disabled="!acknowledged"
            @click="dismissIssued"
        >
            Done
        </button>
    </AppDialog>

    <AppDialog
        v-if="keyToRevoke"
        title="Revoke API key?"
        :description="`Applications using ${keyToRevoke.name} for ${keyToRevoke.environment.name} will immediately lose evaluation access.`"
        @cancel="keyToRevoke = null"
    >
        <p v-if="mutationError" class="mb-4 text-sm text-danger" role="alert">{{ mutationError }}</p>
        <div class="flex gap-3">
            <button
                class="rounded-lg bg-danger px-4 py-2 font-semibold text-on-danger disabled:opacity-60"
                type="button"
                :disabled="isRevoking"
                @click="revoke"
            >
                {{ isRevoking ? 'Revoking…' : 'Revoke API key' }}
            </button>
            <button type="button" :disabled="isRevoking" @click="keyToRevoke = null">Cancel</button>
        </div>
    </AppDialog>
</template>
