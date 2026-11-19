import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// Capacitor/mobile build: static index.html + bundled SPA into dist/.
// The Laravel web app keeps using vite.config.ts (public/build).
export default defineConfig({
    base: './',
    publicDir: false,
    plugins: [react(), tailwindcss()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'react-helmet-async': fileURLToPath(
                new URL('./resources/js/lib/react-helmet-async.tsx', import.meta.url),
            ),
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    router: ['react-router', 'react-router-dom'],
                    query: ['@tanstack/react-query'],
                    ui: ['lucide-react', 'clsx', 'tailwind-merge', 'date-fns', 'axios'],
                },
            },
        },
    },
});
