import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [tailwindcss(), vue()],
    server: {
        port: 5173,
        proxy: {
            '/api': 'http://127.0.0.1:8000',
            '/dashboard': 'http://127.0.0.1:8000',
            '/sanctum': 'http://127.0.0.1:8000',
        },
    },
});
