import { defineConfig, loadEnv } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';

import { toggleFlowAppConfig } from './src/app/app.config';

export default defineConfig(({ mode }) => {
    const apiOrigin = loadEnv(mode, '.', '').TOGGLEFLOW_API_ORIGIN || 'http://127.0.0.1:8000';

    return {
        plugins: [
            vue(),
            ui({
                colorMode: false,
                ui: toggleFlowAppConfig.ui,
                icon: { clientBundle: { scan: true, sizeLimitKb: 64 } },
            }),
        ],
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
