import { defineConfig } from '@playwright/test';

/**
 * Visual audit only: no migrate:fresh, no global seed.
 * Requires app already up (Sail) with usable admin login.
 *
 * Full catalog (85 routes — heavy; avoid on low-RAM hosts):
 *   VISUAL_AUDIT=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
 *     npx playwright test -c playwright.visual-audit.config.ts
 *
 * Selective re-smoke (recommended):
 *   VISUAL_AUDIT=1 VISUAL_AUDIT_PRESET=shells PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
 *     npx playwright test -c playwright.visual-audit.config.ts
 *
 * Ad-hoc routes:
 *   VISUAL_AUDIT=1 VISUAL_AUDIT_ROUTES=/dashboard,/my-approvals \
 *     PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
 *     npx playwright test -c playwright.visual-audit.config.ts
 *
 * Presets: docs/visual-audit/presets.json (shells, wave-0, exceptions, dashboards, smoke)
 * Workers forced to 1 — do not raise on this host.
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
