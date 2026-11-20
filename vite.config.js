import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/icons.css',
                'resources/js/app.js',
                'resources/js/qr-generator.js',
                'resources/js/custom-pc-builder-visual.js',
                'resources/js/booking-phone.js',
                'resources/css/booking-phone.css',
            ],
            refresh: true,
        }),
    ],
});
