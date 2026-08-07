import { defineConfig, loadEnv } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const apiOrigin = loadEnv(mode, '.', '').TOGGLEFLOW_API_ORIGIN || 'http://127.0.0.1:8000';

    return {
        plugins: [tailwindcss(), vue()],
        resolve: {
            alias: {
                '@app': fileURLToPath(new URL('./src/app', import.meta.url)),
                '@features': fileURLToPath(new URL('./src/features', import.meta.url)),
                '@shared': fileURLToPath(new URL('./src/shared', import.meta.url)),
            },
        },
        server: {
            port: 5173,
            proxy: {
                '/api': apiOrigin,
                '/dashboard': apiOrigin,
                '/sanctum': apiOrigin,
            },
        },
    };
});
