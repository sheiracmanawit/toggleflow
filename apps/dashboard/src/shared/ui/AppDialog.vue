<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{
    title: string;
    description: string;
}>();

const emit = defineEmits<{
    cancel: [];
}>();

const panel = ref<HTMLElement | null>(null);
const previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

const focusable = (): HTMLElement[] =>
    Array.from(
        panel.value?.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ) ?? [],
    );

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        event.preventDefault();
        emit('cancel');
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const controls = focusable();
    const first = controls[0];
    const last = controls.at(-1);

    if (!first || !last) {
        event.preventDefault();
        return;
    }

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

onMounted(async () => {
    await nextTick();
    focusable()[0]?.focus();
});

onBeforeUnmount(() => previousFocus?.focus());
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @click.self="emit('cancel')">
            <section
                ref="panel"
                class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="dialog-title"
                aria-describedby="dialog-description"
                @keydown="onKeydown"
            >
                <h2 id="dialog-title" class="text-xl font-semibold">{{ title }}</h2>
                <p id="dialog-description" class="mt-2 text-sm text-slate-600">{{ description }}</p>
                <div class="mt-6">
                    <slot />
                </div>
            </section>
        </div>
    </Teleport>
</template>
