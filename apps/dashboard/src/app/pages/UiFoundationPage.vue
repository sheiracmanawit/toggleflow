<script setup lang="ts">
import { ref } from 'vue';
import { useToast } from '@nuxt/ui/composables';

import ThemePreferenceSelector from '../components/ThemePreferenceSelector.vue';
import { themePreferenceController } from '../theme/themePreference';

const modalOpen = ref(false);
const slideoverOpen = ref(false);
const accepted = ref(false);
const environment = ref('development');
const toast = useToast();

const environments = [
    { label: 'Development', value: 'development' },
    { label: 'Staging', value: 'staging' },
    { label: 'Production', value: 'production' },
];
const rows = [
    { flag: 'New checkout', key: 'new-checkout', state: 'Enabled' },
    { flag: 'Recommendations', key: 'recommendations', state: 'Disabled' },
];
</script>

<template>
    <main class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6" aria-labelledby="foundation-title">
        <UBreadcrumb :items="[{ label: 'Development' }, { label: 'UI foundation' }]" />
        <header>
            <p class="text-sm font-semibold text-primary">Development-only fixture</p>
            <h1 id="foundation-title" class="mt-2 text-3xl font-bold">ToggleFlow UI foundation</h1>
            <p class="mt-2 max-w-3xl text-muted">
                Representative Nuxt UI primitives and ToggleFlow semantic states. This route is not registered in
                production builds.
            </p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <ThemePreferenceSelector />
                <p class="text-sm text-muted" role="status">
                    {{ themePreferenceController.preference.value }} preference resolves to
                    {{ themePreferenceController.resolvedTheme.value }}.
                </p>
            </div>
        </header>

        <section
            class="grid gap-4 rounded-(--radius-surface) border border-border bg-surface p-5 shadow-(--shadow-elevated) md:grid-cols-3"
            aria-labelledby="states-title"
        >
            <h2 id="states-title" class="sr-only">Environment and release states</h2>
            <UBadge color="info" icon="i-lucide-code-2" label="Development" variant="subtle" />
            <UBadge color="warning" icon="i-lucide-flask-conical" label="Staging" variant="subtle" />
            <UBadge
                class="bg-environment-production/10 text-environment-production ring-environment-production/30"
                color="neutral"
                icon="i-lucide-rocket"
                label="Production"
                variant="subtle"
            />
            <UBadge color="success" icon="i-lucide-circle-check" label="Enabled" variant="subtle" />
            <UBadge color="neutral" icon="i-lucide-circle-minus" label="Disabled" variant="subtle" />
            <UBadge color="error" icon="i-lucide-circle-x" label="Failed" variant="subtle" />
        </section>

        <section class="space-y-3" aria-labelledby="brand-semantics-title">
            <h2 id="brand-semantics-title" class="text-xl font-semibold">Cross-theme brand semantics</h2>
            <p class="text-sm text-muted">
                Primary uses one teal/mint family in both presentations. Enabled remains emerald and Production remains
                violet, with explicit labels and icons.
            </p>
            <div class="flex flex-wrap gap-3">
                <UButton icon="i-lucide-sparkles" label="Primary action" />
                <UButton icon="i-lucide-sparkles" label="Primary soft action" variant="soft" />
                <UBadge color="success" icon="i-lucide-circle-check" label="Enabled" variant="subtle" />
                <UBadge
                    class="bg-environment-production/10 text-environment-production ring-environment-production/30"
                    color="neutral"
                    icon="i-lucide-rocket"
                    label="Production"
                    variant="subtle"
                />
            </div>
        </section>

        <section class="space-y-4" aria-labelledby="actions-title">
            <h2 id="actions-title" class="text-xl font-semibold">Actions and request states</h2>
            <div class="flex flex-wrap gap-3">
                <UButton label="Create flag" />
                <UButton color="neutral" label="Secondary action" variant="outline" />
                <UButton color="error" label="Revoke key" />
                <UButton disabled label="Disabled" />
                <UButton loading label="Saving" />
                <UTooltip text="Refresh current data">
                    <UButton
                        aria-label="Refresh current data"
                        color="neutral"
                        icon="i-lucide-refresh-cw"
                        variant="ghost"
                    />
                </UTooltip>
                <UDropdownMenu
                    :items="[
                        [
                            { label: 'Rename', icon: 'i-lucide-pencil' },
                            { label: 'Archive', icon: 'i-lucide-archive' },
                        ],
                    ]"
                >
                    <UButton
                        aria-label="Open project actions"
                        color="neutral"
                        icon="i-lucide-ellipsis"
                        variant="outline"
                    />
                </UDropdownMenu>
            </div>
            <UAlert
                color="success"
                description="The last confirmed server state is preserved."
                icon="i-lucide-circle-check"
                title="Flag updated"
                variant="subtle"
            />
            <UAlert
                color="error"
                description="Try again; no secret or raw server payload is displayed."
                icon="i-lucide-circle-x"
                title="Request failed"
                variant="subtle"
            />
            <div class="grid gap-3 sm:grid-cols-3" aria-label="Loading placeholders">
                <USkeleton class="h-20" /><USkeleton class="h-20" /><USkeleton class="h-20" />
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2" aria-labelledby="forms-title">
            <div class="space-y-4 rounded-(--radius-surface) border border-border bg-surface p-5">
                <h2 id="forms-title" class="text-xl font-semibold">Form composition</h2>
                <UFormField label="Flag name" name="name" required
                    ><UInput class="w-full" placeholder="New checkout"
                /></UFormField>
                <UFormField error="A unique flag key is required." label="Flag key" name="key" required
                    ><UInput aria-describedby="key-error" class="w-full" value="new checkout"
                /></UFormField>
                <UFormField label="Description" name="description"
                    ><UTextarea class="w-full" placeholder="Describe the release behavior"
                /></UFormField>
                <UFormField label="Environment" name="environment"
                    ><USelect v-model="environment" class="w-full" :items="environments"
                /></UFormField>
                <UCheckbox v-model="accepted" label="I understand Production changes require confirmation" />
            </div>
            <div class="space-y-4 rounded-(--radius-surface) border border-border bg-surface p-5">
                <h2 class="text-xl font-semibold">Overlays and feedback</h2>
                <div class="flex flex-wrap gap-3">
                    <UButton label="Open confirmation" @click="modalOpen = true" />
                    <UButton
                        color="neutral"
                        label="Open mobile panel"
                        variant="outline"
                        @click="slideoverOpen = true"
                    />
                    <UButton
                        color="neutral"
                        label="Show toast"
                        variant="soft"
                        @click="
                            toast.add({
                                title: 'Saved',
                                description: 'The confirmed state is current.',
                                color: 'success',
                            })
                        "
                    />
                </div>
                <UModal
                    v-model:open="modalOpen"
                    description="Applications using this Production environment key will begin receiving true."
                    title="Enable new-checkout in Production?"
                >
                    <template #body><p>This change does not deploy application code.</p></template>
                    <template #footer
                        ><UButton color="neutral" label="Cancel" variant="outline" @click="modalOpen = false" /><UButton
                            class="ml-3"
                            label="Confirm Production change"
                            @click="modalOpen = false"
                    /></template>
                </UModal>
                <USlideover
                    v-model:open="slideoverOpen"
                    description="Responsive navigation fixture"
                    title="Project navigation"
                    ><template #body
                        ><nav aria-label="Fixture navigation" class="grid gap-2">
                            <UButton color="neutral" label="Overview" variant="ghost" /><UButton
                                color="neutral"
                                label="Feature flags"
                                variant="ghost"
                            /></nav></template
                ></USlideover>
            </div>
        </section>

        <section class="space-y-4" aria-labelledby="table-title">
            <h2 id="table-title" class="text-xl font-semibold">Operational data</h2>
            <UTable :data="rows" />
            <UPagination aria-label="Feature flag pages" :page="1" :total="24" />
        </section>
    </main>
</template>
