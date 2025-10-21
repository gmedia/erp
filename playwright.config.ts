import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'test-results',
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost',
  },
  reporter: [
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
});
