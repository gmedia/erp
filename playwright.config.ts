import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: 'tests/e2e',
  use: {
    browserName: 'chromium',
  },
  reporter: [
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
});