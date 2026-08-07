export const toggleFlowAppConfig = {
    ui: {
        colors: {
            primary: 'teal',
            secondary: 'teal',
            success: 'emerald',
            info: 'sky',
            warning: 'amber',
            error: 'red',
            neutral: 'zinc',
        },
        icons: {
            check: 'i-lucide-check',
            close: 'i-lucide-x',
            error: 'i-lucide-circle-x',
            info: 'i-lucide-info',
            loading: 'i-lucide-loader-circle',
            menu: 'i-lucide-menu',
            success: 'i-lucide-circle-check',
            warning: 'i-lucide-triangle-alert',
        },
        button: {
            slots: { base: 'min-h-(--control-height) rounded-(--radius-control) font-semibold' },
        },
        input: {
            slots: { base: 'min-h-(--control-height) rounded-(--radius-control)' },
        },
    },
} as const;

export default toggleFlowAppConfig;
