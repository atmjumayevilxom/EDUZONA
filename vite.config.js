import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
            selfDestroying: true,
            includeAssets: ['favicon.ico', 'icons/*.png'],
            manifest: {
                name: 'EDUZONE — AI Ta\'lim Platformasi',
                short_name: 'EDUZONE',
                description: 'O\'qituvchilar uchun AI yordamida interaktiv o\'yin va materiallar yaratish platformasi',
                theme_color: '#6366f1',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
                screenshots: [
                    {
                        src: '/icons/screenshot-wide.png',
                        sizes: '1280x720',
                        type: 'image/png',
                        form_factor: 'wide',
                        label: 'EDUZONE Dashboard',
                    },
                ],
                shortcuts: [
                    {
                        name: 'Yangi o\'yin yaratish',
                        short_name: 'Yaratish',
                        url: '/games/create',
                        icons: [{ src: '/icons/icon-96.png', sizes: '96x96' }],
                    },
                    {
                        name: 'Mening o\'yinlarim',
                        short_name: 'Dashboard',
                        url: '/dashboard',
                        icons: [{ src: '/icons/icon-96.png', sizes: '96x96' }],
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: { cacheName: 'google-fonts-cache', expiration: { maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 } },
                    },
                    {
                        urlPattern: /\/api\/public\/games/,
                        handler: 'NetworkFirst',
                        options: { cacheName: 'api-public-games', expiration: { maxEntries: 50, maxAgeSeconds: 60 * 5 } },
                    },
                ],
            },
        }),
    ],
});
