import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    // Load env variables (including VITE_ALLOWED_ORIGINS) for the current mode
    const env = loadEnv(mode, process.cwd(), '');
    const allowed = env.VITE_ALLOWED_ORIGINS ? env.VITE_ALLOWED_ORIGINS.split(',').map(s => s.trim()).filter(Boolean) : [];

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.js', 'resources/css/app.css',],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],

        // Vite dev server settings. If VITE_ALLOWED_ORIGINS is present in .env,
        // restrict CORS to that list; otherwise enable permissive CORS for dev.
        server: {
            host: false,
            port: 5173,
            cors: { origin: allowed },
        },
    };
});
