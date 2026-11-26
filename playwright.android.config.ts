import { defineConfig } from '@playwright/test';

// Playwright `_android` (experimental) runner for the Capacitor WebView.
// Kept separate from playwright.config.ts: not part of the web E2E shards.
// Run via scripts/e2e-android.sh (boots emulator, installs APK, then runs this).
export default defineConfig({
  testDir: 'tests/e2e-android',
  outputDir: 'e2e/test-results-android',
  timeout: 180000,
  expect: { timeout: 30000 },
  workers: 1,
  retries: 0,
  fullyParallel: false,
  reporter: [['list']],
  use: {
    actionTimeout: 30000,
    navigationTimeout: 90000,
  },
});
