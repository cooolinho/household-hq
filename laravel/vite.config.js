import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/scss/filament/admin/theme.scss',
                'resources/js/filament/admin/fixed-cost-view.js',
                'resources/js/filament/admin/insurance-view.js',
            ],
            refresh: true,
        }),
    ],
});
