<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';

import { authStore } from './stores/auth';
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <nav class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4" aria-label="Primary">
                <RouterLink class="font-semibold text-brand" to="/">ToggleFlow</RouterLink>
                <RouterLink v-if="authStore.isAuthenticated" class="rounded px-3 py-2 text-sm font-medium" to="/app">
                    Dashboard
                </RouterLink>
                <RouterLink v-else class="rounded px-3 py-2 text-sm font-medium" to="/sign-in">Sign in</RouterLink>
            </nav>
        </header>
        <main class="mx-auto max-w-5xl px-6 py-12">
            <RouterView />
        </main>
    </div>
</template>
