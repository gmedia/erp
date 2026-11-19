import 'dotenv/config';
import type { CapacitorConfig } from '@capacitor/cli';

const appName = process.env.APP_NAME ?? process.env.VITE_APP_NAME ?? 'ERP';

const config: CapacitorConfig = {
    // Placeholder appId — sticky once published; replace before any release.
    appId: 'com.contoh.erp',
    appName,
    webDir: 'dist',
    ...(process.env.NODE_ENV !== 'production' ? { loggingBehavior: 'debug' } : {}),
};

export default config;
