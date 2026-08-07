<script setup lang="ts">
import { themePreferenceController, themePreferences, type ThemePreference } from '../theme/themePreference';

const labels: Record<ThemePreference, string> = { light: 'Light', dark: 'Dark', system: 'System' };
const icons: Record<ThemePreference, string> = {
    light: 'i-lucide-sun',
    dark: 'i-lucide-moon',
    system: 'i-lucide-monitor',
};
</script>

<template>
    <div class="flex items-center gap-1" role="group" aria-label="Theme preference">
        <UButton
            v-for="option in themePreferences"
            :key="option"
            :aria-label="`Use ${labels[option]} theme`"
            :aria-pressed="themePreferenceController.preference.value === option"
            color="neutral"
            :icon="icons[option]"
            size="sm"
            :variant="themePreferenceController.preference.value === option ? 'soft' : 'ghost'"
            @click="themePreferenceController.setPreference(option)"
        >
            <span class="hidden xl:inline">{{ labels[option] }}</span>
        </UButton>
    </div>
</template>
