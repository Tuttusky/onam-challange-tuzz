import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/spa.css',
                'resources/js/spa/main.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
            ],
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
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'Onam Dare Challenge',
                short_name: 'OnamDare',
                theme_color: '#6366f1',
                background_color: '#0f172a',
                display: 'standalone',
                start_url: '/',
                icons: [
                    { src: '/favicon.ico', sizes: '64x64', type: 'image/x-icon' },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/spa', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
