import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import {
    defineConfig
} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Browser must use localhost — never 0.0.0.0 (breaks CSS/JS and can block the page).
        origin: 'http://localhost:5173',
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: process.env.CHOKIDAR_USEPOLLING === 'true',
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});