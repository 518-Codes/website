import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // VITE_HOST is set only by the Docker dev container; on the host
        // (Herd) these stay undefined and Vite uses its defaults.
        host: process.env.VITE_HOST,
        // VITE_ORIGIN / VITE_HMR_HOST let the dev container advertise a LAN-reachable
        // address so the app's @vite asset URLs and the HMR socket resolve from other
        // devices (must not be "localhost", which points at the visiting device).
        origin: process.env.VITE_ORIGIN || undefined,
        // The page (app, :8000) fetches modules/HMR cross-origin from Vite (:5180).
        // Vite 8 locks CORS down by default; reflect the requesting origin so LAN
        // devices can load assets. Dev container only.
        cors: process.env.VITE_HOST ? { origin: true } : undefined,
        hmr: process.env.VITE_HMR_HOST
            ? { host: process.env.VITE_HMR_HOST }
            : (process.env.VITE_HOST ? { host: 'localhost' } : undefined),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
