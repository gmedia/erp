import { defineConfig } from '@playwright/test';

const configuredWorkers = Number.parseInt(process.env.PLAYWRIGHT_WORKERS ?? '1', 10);
const workers = Number.isFinite(configuredWorkers) && configuredWorkers > 0
  ? configuredWorkers
  : 1;
const shouldPreloadAuthState = process.env.PLAYWRIGHT_PRELOAD_AUTH !== '0';
const storageStatePath = process.env.PLAYWRIGHT_STORAGE_STATE ?? 'e2e/.auth/admin.json';

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'e2e/test-results',
  timeout: 60000,
  globalSetup: './tests/e2e/global-setup',
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:80',
    ...(shouldPreloadAuthState ? { storageState: storageStatePath } : {}),
    actionTimeout: 15000,
  },
  fullyParallel: false,
  workers,
  reporter: [
    ['html', { outputFolder: 'e2e/playwright-report', open: 'never' }],
  ],
});
