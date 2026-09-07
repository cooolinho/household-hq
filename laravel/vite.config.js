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
                'resources/scss/filament/app/theme.scss',
                'resources/scss/filament/admin/theme.scss',
                'resources/js/filament/app/fixed-cost-view.js',
                'resources/js/filament/app/insurance-view.js',
            ],
            refresh: true,
        }),
    ],
});
