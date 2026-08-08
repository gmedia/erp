import { defineConfig } from '@playwright/test';

/**
 * Visual audit only: no migrate:fresh, no global seed.
 * Requires app already up (Sail) with usable admin login.
 *
 * VISUAL_AUDIT=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
 *   npx playwright test -c playwright.visual-audit.config.ts
 */
export default defineConfig({
  testDir: 'tests/e2e/visual-audit',
  outputDir: 'e2e/test-results-visual-audit',
  timeout: 90_000,
  fullyParallel: false,
  workers: 1,
  reporter: [['list']],
  use: {
    browserName: 'chromium',
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:82',
    actionTimeout: 15_000,
    viewport: { width: 1440, height: 900 },
  },
});
