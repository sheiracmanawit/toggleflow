<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import { useApiKeyLifecycle } from '../composables/useApiKeyLifecycle';

const {
    route,
    project,
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
    <section aria-labelledby="api-keys-heading">
        <RouterLink
            class="text-sm font-semibold text-brand hover:underline"
            :to="`/projects/${route.params.projectId}`"
        >
            ← Project overview
        </RouterLink>
        <p v-if="isLoading" class="mt-8 rounded-xl border border-slate-200 bg-white p-6" role="status">
            Loading API keys…
        </p>
        <div v-else-if="loadError" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h1 id="api-keys-heading" class="text-xl font-semibold">API keys unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="project">
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand">{{ project.name }}</p>
                    <h1 id="api-keys-heading" class="mt-1 text-3xl font-bold">API keys</h1>
                    <p class="mt-2 max-w-2xl text-slate-600">
                        Server-side applications use a key for exactly one environment. Complete keys are shown once.
                    </p>
                </div>
                <button
                    v-if="project.status === 'active'"
                    class="self-start rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand"
                    type="button"
                    @click="showCreate = true"
                >
                    Issue API key
                </button>
            </div>
            <p v-if="successMessage" class="mt-4 text-sm font-semibold text-enabled" role="status">
                {{ successMessage }}
            </p>
            <p v-if="mutationError" class="mt-4 text-sm font-semibold text-danger" role="alert">
                {{ mutationError }}
            </p>
            <p v-if="project.status === 'archived'" class="mt-4 text-sm text-slate-600">
                This project is archived. Credential metadata remains available for reference, but credentials cannot be
                issued or revoked.
            </p>

            <div class="mt-8 grid gap-6">
                <section
                    v-for="environment in project.environments"
                    :key="environment.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5"
                    :aria-labelledby="`environment-${environment.id}`"
                >
                    <h2 :id="`environment-${environment.id}`" class="text-xl font-semibold">
                        {{ environment.name }}
                    </h2>
                    <p class="mt-1 font-mono text-sm text-slate-500">{{ environment.key }}</p>
                    <p v-if="keysFor(environment).length === 0" class="mt-5 text-sm text-slate-600">
                        No credentials have been issued for this environment.
                    </p>
                    <ul v-else class="mt-5 grid gap-3">
                        <li
                            v-for="apiKey in keysFor(environment)"
                            :key="apiKey.id"
                            class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold">{{ apiKey.name }}</h3>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold">
                                        {{ apiKey.state === 'active' ? 'Active' : 'Revoked' }}
                                    </span>
                                </div>
                                <p class="mt-2 break-all font-mono text-sm text-slate-600">
                                    tf_env_{{ apiKey.prefix }}_…
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Created {{ new Date(apiKey.created_at).toLocaleString() }}
                                    <template v-if="apiKey.last_used_at">
                                        · Last used {{ new Date(apiKey.last_used_at).toLocaleString() }}
                                    </template>
                                </p>
                            </div>
                            <button
                                v-if="project.status === 'active' && apiKey.state === 'active'"
                                class="self-start rounded-lg border border-red-300 px-3 py-2 font-semibold text-danger"
                                type="button"
                                @click="
                                    mutationError = '';
                                    keyToRevoke = apiKey;
                                "
                            >
                                Revoke
                            </button>
                        </li>
                    </ul>
                </section>
            </div>

            <form
                v-if="showCreate"
                class="mt-8 rounded-2xl border border-slate-200 bg-white p-6"
                novalidate
                @submit.prevent="issue"
            >
                <h2 class="text-xl font-semibold">Issue an environment API key</h2>
                <div class="mt-5 grid gap-5">
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
                            <option
                                v-for="environment in project.environments"
                                :key="environment.id"
                                :value="environment.id"
                            >
                                {{ environment.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button
                            class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand"
                            :disabled="isSubmitting"
                        >
                            {{ isSubmitting ? 'Issuing…' : 'Issue API key' }}
                        </button>
                        <button type="button" :disabled="isSubmitting" @click="showCreate = false">Cancel</button>
                    </div>
                </div>
            </form>
        </template>
    </section>

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
