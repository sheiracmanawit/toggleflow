import vue from '@vitejs/plugin-vue';
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
    plugins: [vue()],
    test: {
        environment: 'happy-dom',
        include: ['src/**/*.spec.ts'],
    },
});
