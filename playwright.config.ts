import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'e2e/test-results',
  timeout: 60000,
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:80',
    actionTimeout: 15000,
  },
  fullyParallel: false,
  workers: 1,
  reporter: [
    ['html', { outputFolder: 'e2e/playwright-report', open: 'never' }],
  ],
});
