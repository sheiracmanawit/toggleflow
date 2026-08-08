<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import { useApiKeyLifecycle } from '../composables/useApiKeyLifecycle';

const {
    route,
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
    keysFor,
    environmentExample,
    load,
    issue,
    copyCredential,
    dismissIssued,
    revoke,
} = useApiKeyLifecycle();
</script>

<template>
    <section class="-mx-4 -my-6 sm:-mx-6 sm:-my-8" aria-label="API keys">
        <p v-if="isLoading" class="p-6" role="status">Loading API keys…</p>
        <div v-else-if="loadError" class="m-6 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <h1 class="text-base font-semibold">API keys unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <UDashboardToolbar class="border-b border-border px-4 py-3 sm:px-6">
                <template #left>
                    <RouterLink
                        class="text-sm font-medium text-text-muted hover:text-text"
                        :to="`/projects/${route.params.projectId}`"
                    >
                        ← Project overview
                    </RouterLink>
                    <span class="text-sm font-semibold text-text">{{ project.name }}</span>
                    <span class="hidden text-sm text-text-muted sm:inline">
                        {{ apiKeys.length }} credentials across {{ project.environments.length }} environments
                    </span>
                </template>
                <template #right>
                    <UButton
                        v-if="project.status === 'active'"
                        icon="i-lucide-plus"
                        size="sm"
                        type="button"
                        @click="showCreate = true"
                    >
                        Issue API key
                    </UButton>
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

            <section aria-label="API key inventory">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-2 sm:px-6">
                    <ul class="flex flex-wrap gap-2" aria-label="Environment credential counts">
                        <li
                            v-for="environment in project.environments"
                            :key="environment.id"
                            class="rounded-md bg-surface-muted px-2 py-1 text-xs"
                        >
                            <span class="font-semibold">{{ environment.name }}</span>
                            {{ keysFor(environment).length }}
                        </li>
                    </ul>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[46rem] w-full border-collapse text-left text-sm">
                        <thead class="border-b border-border bg-surface-muted/50 text-xs text-text-muted">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold" scope="col">Environment</th>
                                <th class="px-4 py-2.5 font-semibold" scope="col">Credential</th>
                                <th class="px-4 py-2.5 font-semibold" scope="col">Status</th>
                                <th class="px-4 py-2.5 font-semibold" scope="col">Activity</th>
                                <th class="px-4 py-2.5 text-right font-semibold" scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <template v-for="environment in project.environments" :key="environment.id">
                                <tr v-if="keysFor(environment).length === 0">
                                    <th class="px-4 py-3 align-top font-semibold" scope="row">
                                        {{ environment.name }}
                                        <span class="block font-mono text-xs font-normal text-slate-500">{{
                                            environment.key
                                        }}</span>
                                    </th>
                                    <td class="px-4 py-3 text-slate-500" colspan="4">No credentials issued</td>
                                </tr>
                                <tr v-for="apiKey in keysFor(environment)" v-else :key="apiKey.id">
                                    <th class="px-4 py-3 align-top font-semibold" scope="row">
                                        {{ environment.name }}
                                        <span class="block font-mono text-xs font-normal text-slate-500">{{
                                            environment.key
                                        }}</span>
                                    </th>
                                    <td class="px-4 py-3 align-top">
                                        <span class="block font-semibold">{{ apiKey.name }}</span>
                                        <span class="block font-mono text-xs text-slate-500"
                                            >tf_env_{{ apiKey.prefix }}_…</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold">
                                            {{ apiKey.state === 'active' ? 'Active' : 'Revoked' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-xs text-slate-600">
                                        <span class="block"
                                            >Created {{ new Date(apiKey.created_at).toLocaleString() }}</span
                                        >
                                        <span v-if="apiKey.last_used_at" class="block">
                                            Last used {{ new Date(apiKey.last_used_at).toLocaleString() }}
                                        </span>
                                        <span v-else class="block">Never used</span>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <UButton
                                            v-if="project.status === 'active' && apiKey.state === 'active'"
                                            aria-label="Revoke API key"
                                            color="error"
                                            icon="i-lucide-key-round"
                                            size="xs"
                                            type="button"
                                            variant="ghost"
                                            @click="
                                                mutationError = '';
                                                keyToRevoke = apiKey;
                                            "
                                        >
                                            Revoke
                                        </UButton>
                                        <span v-else class="text-xs text-slate-500">—</span>
                                    </td>
                                </tr>
                            </template>
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
