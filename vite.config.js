import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/frontend/js/app.js',
                'resources/frontend/js/public.js',
                'resources/frontend/sass/app.scss',
                'resources/frontend/app.css',
                'resources/frontend/public.css',
                'resources/backend/app.css',
            ],
            refresh: true,
        }),
        vue(),
    ],
    // Default outDir is public/build — required for local php artisan serve.
    // (Do not use ../public_html/build here; that is Cloudways-only.)
});
