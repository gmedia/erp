import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'e2e/test-results',
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:81',
  },
  reporter: [
    ['html', { outputFolder: 'e2e/playwright-report', open: 'never' }],
  ],
});
