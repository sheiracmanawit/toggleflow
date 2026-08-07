import vue from '@vitejs/plugin-vue';
import ui from '@nuxt/ui/vite';
import { defineConfig } from 'vitest/config';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    resolve: {
        alias: {
            '@app': fileURLToPath(new URL('./src/app', import.meta.url)),
            '@features': fileURLToPath(new URL('./src/features', import.meta.url)),
            '@shared': fileURLToPath(new URL('./src/shared', import.meta.url)),
        },
    },
    plugins: [vue(), ui({ colorMode: false, icon: { clientBundle: { scan: true, sizeLimitKb: 64 } } })],
    test: {
        environment: 'happy-dom',
        include: ['src/**/*.spec.ts'],
        setupFiles: ['src/app/testSetup.ts'],
    },
});
