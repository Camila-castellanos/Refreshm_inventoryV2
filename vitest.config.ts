import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./resources/js/__tests__/setup.ts'],
        include: ['resources/js/**/*.{test,spec}.{js,ts}'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html'],
            exclude: [
                'node_modules/',
                'resources/js/__tests__/',
                '**/*.d.ts',
            ],
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, './resources/js'),
        },
    },
});
