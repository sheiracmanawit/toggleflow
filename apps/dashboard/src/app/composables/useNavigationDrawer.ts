import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

export const useNavigationDrawer = () => {
    const drawerOpen = ref(false);
    const drawer = ref<HTMLElement | null>(null);
    const drawerButton = ref<HTMLElement | null>(null);
    const drawerCloseButton = ref<HTMLElement | null>(null);
    let previousBodyOverflow = '';
    let scrollLocked = false;

    const lockBackgroundScroll = (): void => {
        if (scrollLocked) return;
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        scrollLocked = true;
    };

    const unlockBackgroundScroll = (): void => {
        if (!scrollLocked) return;
        document.body.style.overflow = previousBodyOverflow;
        scrollLocked = false;
    };

    const openDrawer = async (): Promise<void> => {
        lockBackgroundScroll();
        drawerOpen.value = true;
        await nextTick();
        drawerCloseButton.value?.focus();
    };

    const closeDrawer = async (): Promise<void> => {
        drawerOpen.value = false;
        unlockBackgroundScroll();
        await nextTick();
        drawerButton.value?.focus();
    };

    const closeDrawerAtDesktopBreakpoint = (): void => {
        if (window.innerWidth >= 768 && drawerOpen.value) {
            drawerOpen.value = false;
            unlockBackgroundScroll();
        }
    };

    const handleDrawerKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Escape') {
            void closeDrawer();
            return;
        }
        if (event.key !== 'Tab' || !drawer.value) return;

        const focusable = Array.from(
            drawer.value.querySelectorAll<HTMLElement>(
                'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
            ),
        );
        if (focusable.length === 0) return;

        const first = focusable[0];
        const last = focusable.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    };

    onMounted(() => window.addEventListener('resize', closeDrawerAtDesktopBreakpoint));
    onBeforeUnmount(() => {
        window.removeEventListener('resize', closeDrawerAtDesktopBreakpoint);
        unlockBackgroundScroll();
    });

    return { drawerOpen, drawer, drawerButton, drawerCloseButton, openDrawer, closeDrawer, handleDrawerKeydown };
};
