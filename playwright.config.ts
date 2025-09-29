import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'tmp-test-results',
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost',
  },
  reporter: [
    ['html', { outputFolder: 'tmp-playwright-report', open: 'never' }],
  ],
});
