import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import {VitePWA} from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/api\.openweathermap\.org\/.*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'weather-api-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60, // 1 hour
              },
              cacheableResponse: {
                statuses: [0, 200],
              },
            },
          },
        ],
      },
      manifest: {
        name: 'SkyScope Weather',
        short_name: 'SkyScope',
        description: 'Check the weather and get clothing recommendations for your location or any city!',
        theme_color: '#3b82f6',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        scope: '/',
        start_url: '/',
        icons: [
          {
            src: '/icons/icon-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any maskable',
          },
          {
            src: '/icons/icon-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable',
          },
        ],
        shortcuts: [
          {
            name: 'Current Location',
            short_name: 'Location',
            description: 'Get weather for your current location',
            url: '/?action=location',
            icons: [{ src: '/icons/icon-192x192.png', sizes: '192x192' }],
          },
          {
            name: 'Search City',
            short_name: 'Search',
            description: 'Search weather for a city',
            url: '/?action=search',
            icons: [{ src: '/icons/icon-192x192.png', sizes: '192x192' }],
          },
        ],
        categories: ['weather', 'utilities', 'lifestyle'],
        screenshots: [],
      },
      devOptions: {
        enabled: false,
        type: 'module',
      },
    }),
  ],
  server: {
    host: true, // Allow external connections (needed for ngrok)
    port: 5173, // Explicitly set port
    strictPort: false, // Allow fallback to next available port if 5173 is taken
    proxy: {
      '/weather': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/weather/, '/weather.php'),
      },
    },
    allowedHosts: [
      'autographic-gorillian-ebony.ngrok-free.dev', // your ngrok URL
      '.ngrok-free.dev', // Allow all ngrok free domains
      '.ngrok.io', // Allow all ngrok domains
    ],
  },
});
