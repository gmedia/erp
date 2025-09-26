import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost',
  },
  reporter: [
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
});
