import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const apiOrigin = loadEnv(mode, '.', '').TOGGLEFLOW_API_ORIGIN || 'http://127.0.0.1:8000';

    return {
        plugins: [tailwindcss(), vue()],
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
