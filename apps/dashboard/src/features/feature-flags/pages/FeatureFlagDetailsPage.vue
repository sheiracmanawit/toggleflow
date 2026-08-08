<script setup lang="ts">
import { RouterLink } from 'vue-router';

import { AppDialog } from '@shared/ui';
import { useFeatureFlagDetails } from '../composables/useFeatureFlagDetails';

const {
    route,
    flag,
    isLoading,
    loadError,
    isEditing,
    isSaving,
    saveError,
    successMessage,
    validationErrors,
    form,
    pendingEnvironmentId,
    stateError,
    confirmation,
    showArchiveDialog,
    isArchiving,
    archiveError,
    load,
    startEditing,
    save,
    requestStateChange,
    changeState,
    archive,
} = useFeatureFlagDetails();
</script>

<template>
    <section aria-labelledby="flag-heading">
        <RouterLink
            class="text-sm font-semibold text-brand hover:underline"
            :to="`/projects/${route.params.projectId}/flags`"
        >
            ← Feature flags
        </RouterLink>
        <p v-if="isLoading" class="mt-8 rounded-xl border border-slate-200 bg-white p-6" role="status">
            Loading feature flag…
        </p>
        <div v-else-if="loadError" class="mt-8 rounded-xl border border-red-200 bg-red-50 p-6" role="alert">
            <h1 id="flag-heading" class="text-xl font-semibold">Feature flag unavailable</h1>
            <p class="mt-2">{{ loadError }}</p>
            <button class="mt-3 font-semibold text-danger underline" type="button" @click="load">Try again</button>
        </div>
        <template v-else-if="flag">
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="break-all font-mono text-sm text-slate-500">{{ flag.key }}</p>
                    <div class="mt-1 flex items-center gap-3">
                        <h1 id="flag-heading" class="text-3xl font-bold">{{ flag.name }}</h1>
                        <span
                            class="rounded-full px-3 py-1 text-sm font-semibold"
                            :class="
                                flag.status === 'active' ? 'bg-emerald-100 text-enabled' : 'bg-slate-200 text-slate-700'
                            "
                            :aria-label="`Flag lifecycle: ${flag.status === 'active' ? 'Active' : 'Archived'}`"
                        >
                            {{ flag.status === 'active' ? 'Active' : 'Archived' }}
                        </span>
                    </div>
                    <p class="mt-2 text-slate-600">{{ flag.description || 'No flag description yet.' }}</p>
                </div>
                <button
                    v-if="flag.status === 'active'"
                    class="self-start rounded-lg border border-slate-300 bg-white px-4 py-2 font-semibold"
                    type="button"
                    @click="startEditing"
                >
                    Edit details
                </button>
            </div>
            <p v-if="successMessage" class="mt-4 text-sm font-medium text-enabled" role="status">
                {{ successMessage }}
            </p>

            <section class="mt-8" aria-labelledby="states-heading">
                <h2 id="states-heading" class="text-xl font-semibold">Environment state</h2>
                <p class="mt-1 text-sm text-slate-600">Each environment is controlled independently.</p>
                <p
                    v-if="stateError"
                    class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-danger"
                    role="alert"
                >
                    {{ stateError }}
                </p>
                <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                    <li
                        v-for="state in flag.environment_states"
                        :key="state.environment.id"
                        class="rounded-2xl border bg-white p-5"
                        :class="state.environment.key === 'production' ? 'border-violet-300' : 'border-slate-200'"
                    >
                        <h3 class="font-semibold">{{ state.environment.name }}</h3>
                        <p class="mt-2 font-semibold" :class="state.enabled ? 'text-enabled' : 'text-disabled'">
                            {{ state.enabled ? 'Enabled' : 'Disabled' }}
                        </p>
                        <button
                            v-if="flag.status === 'active'"
                            class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 font-semibold disabled:opacity-60"
                            type="button"
                            role="switch"
                            :aria-checked="state.enabled"
                            :aria-label="`${state.enabled ? 'Disable' : 'Enable'} ${flag.name} in ${state.environment.name}`"
                            :disabled="pendingEnvironmentId !== null"
                            @click="requestStateChange(state)"
                        >
                            {{
                                pendingEnvironmentId === state.environment.id
                                    ? 'Saving…'
                                    : state.enabled
                                      ? 'Disable'
                                      : 'Enable'
                            }}
                        </button>
                    </li>
                </ul>
            </section>

            <AppDialog
                v-if="flag.status === 'active' && isEditing"
                title="Edit flag details"
                :description="`Update the display name or description. The immutable key remains ${flag.key}.`"
                @cancel="!isSaving && (isEditing = false)"
            >
                <form class="grid gap-5" novalidate @submit.prevent="save">
                    <div>
                        <label class="block text-sm font-semibold" for="edit-flag-name">Name</label>
                        <input
                            id="edit-flag-name"
                            v-model="form.name"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
                            :aria-describedby="validationErrors.name ? 'edit-flag-name-error' : undefined"
                            :aria-invalid="Boolean(validationErrors.name)"
                        />
                        <p v-if="validationErrors.name" id="edit-flag-name-error" class="mt-1 text-sm text-danger">
                            {{ validationErrors.name[0] }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold" for="edit-flag-description"
                            >Description (optional)</label
                        >
                        <textarea
                            id="edit-flag-description"
                            v-model="form.description"
                            class="mt-1 min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2"
                            :aria-describedby="validationErrors.description ? 'edit-flag-description-error' : undefined"
                            :aria-invalid="Boolean(validationErrors.description)"
                        />
                        <p
                            v-if="validationErrors.description"
                            id="edit-flag-description-error"
                            class="mt-1 text-sm text-danger"
                        >
                            {{ validationErrors.description[0] }}
                        </p>
                    </div>
                    <p v-if="saveError" class="text-sm text-danger" role="alert">{{ saveError }}</p>
                    <div class="flex gap-3">
                        <button
                            class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand disabled:opacity-60"
                            type="submit"
                            :disabled="isSaving"
                        >
                            {{ isSaving ? 'Saving…' : 'Save details' }}
                        </button>
                        <button
                            class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                            type="button"
                            :disabled="isSaving"
                            @click="isEditing = false"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </AppDialog>

            <section
                v-if="flag.status === 'active'"
                class="mt-10 rounded-2xl border border-red-200 bg-red-50 p-6"
                aria-labelledby="archive-flag-heading"
            >
                <h2 id="archive-flag-heading" class="text-lg font-semibold text-danger">Archive flag</h2>
                <p class="mt-2 text-sm text-slate-700">
                    The flag will leave active views while its environment state and history are retained.
                </p>
                <button
                    class="mt-4 rounded-lg bg-danger px-4 py-2 font-semibold text-on-danger"
                    type="button"
                    @click="showArchiveDialog = true"
                >
                    Archive flag
                </button>
            </section>
        </template>

        <AppDialog
            v-if="confirmation && flag"
            :title="`${confirmation.enabled ? 'Enable' : 'Disable'} “${flag.name}” in Production?`"
            :description="`Applications using this Production environment key will begin receiving ${confirmation.enabled}. This change does not deploy application code.`"
            @cancel="confirmation = null"
        >
            <div class="flex justify-end gap-3">
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                    type="button"
                    :disabled="pendingEnvironmentId !== null"
                    @click="confirmation = null"
                >
                    Cancel
                </button>
                <button
                    class="rounded-lg bg-brand px-4 py-2 font-semibold text-on-brand disabled:opacity-60"
                    type="button"
                    :disabled="pendingEnvironmentId !== null"
                    @click="changeState(confirmation.state, confirmation.enabled)"
                >
                    {{ confirmation.enabled ? 'Enable in Production' : 'Disable in Production' }}
                </button>
            </div>
        </AppDialog>

        <AppDialog
            v-if="showArchiveDialog && flag"
            :title="`Archive ${flag.name}?`"
            description="This flag will leave active flag views. Its environment state and audit history will be retained."
            @cancel="!isArchiving && (showArchiveDialog = false)"
        >
            <p v-if="archiveError" class="mb-4 text-sm text-danger" role="alert">{{ archiveError }}</p>
            <div class="flex justify-end gap-3">
                <button
                    class="rounded-lg border border-slate-300 px-4 py-2 font-semibold"
                    type="button"
                    :disabled="isArchiving"
                    @click="showArchiveDialog = false"
                >
                    Keep flag
                </button>
                <button
                    class="rounded-lg bg-danger px-4 py-2 font-semibold text-on-danger disabled:opacity-60"
                    type="button"
                    :disabled="isArchiving"
                    @click="archive"
                >
                    {{ isArchiving ? 'Archiving…' : 'Archive flag' }}
                </button>
            </div>
        </AppDialog>
    </section>
</template>
