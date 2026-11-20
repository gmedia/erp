import { defineConfig, loadEnv, mergeConfig } from 'vite';
import base from './vite.config';

/**
 * Vite dev server untuk diakses dari laptop lewat SSH tunnel (`npm run dev:tunnel`).
 *
 * - Listen di semua interface **di dalam container** agar port yang dipublish Docker
 *   (`${HOST_IP}:${VITE_PORT}`, HOST_IP=127.0.0.1) bisa dijangkau.
 * - `origin`/HMR memakai `localhost:<VITE_PORT>` sehingga `public/hot` tidak berisi
 *   `http://0.0.0.0:...` (ditolak browser).
 *
 * Laptop wajib men-tunnel port Vite dengan nomor yang sama, lihat AGENTS.md
 * → "Akses dari laptop (SSH tunnel)".
 */
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_PORT || 5173);

    return mergeConfig(base, {
        server: {
            host: '0.0.0.0',
            port,
            strictPort: true,
            origin: `http://localhost:${port}`,
            hmr: { host: 'localhost', port },
            cors: true,
        },
    });
});
