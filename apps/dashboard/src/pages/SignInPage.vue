<script setup lang="ts">
import axios from 'axios';
import { nextTick, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { safeRedirect } from '../router';
import { authService } from '../services/auth';
import { authStore } from '../stores/auth';
import type { DemoCredentials } from '../types/auth';

const route = useRoute();
const router = useRouter();
const form = reactive({ email: '', password: '' });
const errors = reactive<{ email?: string; password?: string }>({});
const statusMessage = ref(authStore.consumeMessage());
const isSubmitting = ref(false);
const demoCredentials = ref<DemoCredentials | null>(null);
const errorSummary = ref<{ focus: () => void } | null>(null);

const validate = (): boolean => {
    errors.email = undefined;
    errors.password = undefined;

    if (!form.email.trim()) {
        errors.email = 'Enter your email address.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        errors.email = 'Enter a valid email address.';
    }

    if (!form.password) {
        errors.password = 'Enter your password.';
    }

    return !errors.email && !errors.password;
};

const focusError = async (): Promise<void> => {
    await nextTick();
    errorSummary.value?.focus();
};

const submit = async (): Promise<void> => {
    statusMessage.value = '';

    if (!validate()) {
        statusMessage.value = 'Check the highlighted fields.';
        await focusError();
        return;
    }

    isSubmitting.value = true;

    try {
        await authStore.login({ email: form.email, password: form.password });
        await router.replace(safeRedirect(route.query.redirect));
    } catch (error: unknown) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            const validation = error.response.data?.errors as Record<string, string[]> | undefined;
            errors.email = validation?.email?.[0];
            errors.password = validation?.password?.[0];
            statusMessage.value = 'Check the highlighted fields.';
        } else if (axios.isAxiosError(error) && error.response?.status === 429) {
            statusMessage.value = 'Too many sign-in attempts. Please try again later.';
        } else {
            statusMessage.value = 'The provided credentials are invalid.';
        }

        await focusError();
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(async () => {
    try {
        demoCredentials.value = await authService.demoCredentials();
    } catch {
        demoCredentials.value = null;
    }
});
</script>

<template>
    <section class="mx-auto max-w-md" aria-labelledby="sign-in-heading">
        <h1 id="sign-in-heading" class="text-3xl font-bold">Sign in</h1>
        <p class="mt-2 text-slate-600">Access your projects and release settings.</p>

        <div
            v-if="statusMessage"
            ref="errorSummary"
            class="mt-6 rounded-lg border border-danger/30 bg-red-50 p-4 text-sm text-danger"
            role="alert"
            tabindex="-1"
        >
            {{ statusMessage }}
        </div>

        <form class="mt-6 space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium" for="email">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    name="email"
                    type="email"
                    autocomplete="username"
                    :aria-describedby="errors.email ? 'email-error' : undefined"
                    :aria-invalid="Boolean(errors.email)"
                />
                <p v-if="errors.email" id="email-error" class="mt-2 text-sm text-danger">{{ errors.email }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium" for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    :aria-describedby="errors.password ? 'password-error' : undefined"
                    :aria-invalid="Boolean(errors.password)"
                />
                <p v-if="errors.password" id="password-error" class="mt-2 text-sm text-danger">{{ errors.password }}</p>
            </div>

            <button
                class="w-full rounded-lg bg-brand px-4 py-2.5 font-semibold text-white disabled:cursor-wait disabled:opacity-60"
                type="submit"
                :disabled="isSubmitting"
            >
                {{ isSubmitting ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>

        <aside v-if="demoCredentials" class="mt-6 rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm">
            <h2 class="font-semibold">Local demo account</h2>
            <p class="mt-2"><span class="font-medium">Email:</span> {{ demoCredentials.email }}</p>
            <p><span class="font-medium">Password:</span> {{ demoCredentials.password }}</p>
        </aside>
    </section>
</template>
