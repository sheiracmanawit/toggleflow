<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { useAuthStore } from '../stores';

const router = useRouter();
const authStore = useAuthStore();
const isSigningOut = ref(false);
const signOutError = ref('');

const signOut = async (): Promise<void> => {
    isSigningOut.value = true;
    signOutError.value = '';

    try {
        await authStore.logout();
        await router.replace('/sign-in');
    } catch {
        signOutError.value = 'ToggleFlow could not sign you out. Please try again.';
    } finally {
        isSigningOut.value = false;
    }
};
</script>

<template>
    <section aria-labelledby="dashboard-heading">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 id="dashboard-heading" class="text-3xl font-bold">Dashboard</h1>
                <p v-if="authStore.owner" class="mt-2 text-slate-600">
                    Signed in as {{ authStore.owner.name }} ({{ authStore.owner.email }})
                </p>
            </div>
            <button
                class="self-start rounded-lg border border-slate-300 bg-white px-4 py-2 font-medium disabled:cursor-wait disabled:opacity-60"
                type="button"
                :disabled="isSigningOut"
                @click="signOut"
            >
                {{ isSigningOut ? 'Signing out…' : 'Sign out' }}
            </button>
        </div>
        <p v-if="signOutError" class="mt-4 text-sm text-danger" role="alert">{{ signOutError }}</p>
        <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold">Secure owner access is ready</h2>
            <p class="mt-2 text-slate-600">Project and feature management arrive in the next MVP stories.</p>
        </div>
    </section>
</template>
