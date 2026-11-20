import 'dotenv/config';
import type { CapacitorConfig } from '@capacitor/cli';

const appId = process.env.CAPACITOR_APP_ID ?? 'com.dokfin.erp';
const appName =
    process.env.CAPACITOR_APP_NAME ?? process.env.APP_NAME ?? 'ERP';

const config: CapacitorConfig = {
    appId,
    appName,
    webDir: 'dist',
    ...(process.env.NODE_ENV !== 'production' ? { loggingBehavior: 'debug' } : {}),
};

export default config;
