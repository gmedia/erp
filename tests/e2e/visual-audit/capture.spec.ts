/**
 * Opt-in visual audit capture (VISUAL_AUDIT=1). Not part of default E2E.
 *
 * VISUAL_AUDIT=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 PLAYWRIGHT_WORKERS=1 \
 *   npx playwright test tests/e2e/visual-audit/capture.spec.ts
 *
 * VISUAL_AUDIT_ROUTES=/dashboard,/departments
 * VISUAL_AUDIT_WAVE=wave-0
 */
import { test, expect } from '@playwright/test';
import { mkdirSync, readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { login } from '../helpers';

const enabled = process.env.VISUAL_AUDIT === '1';
const wave = process.env.VISUAL_AUDIT_WAVE ?? 'wave-0';
const outDir = join('docs/visual-audit/waves', wave);
const urlListPath = 'docs/visual-audit/url-list.json';

function loadRoutes(): string[] {
  const override = process.env.VISUAL_AUDIT_ROUTES?.trim();
  if (override) {
    return override
      .split(',')
      .map((r) => r.trim())
      .filter(Boolean)
      .map((r) => (r.startsWith('/') ? r : `/${r}`));
  }

  if (!existsSync(urlListPath)) {
    return ['/dashboard'];
  }

  const raw = JSON.parse(readFileSync(urlListPath, 'utf8')) as { routes?: string[] };
  return raw.routes?.length ? raw.routes : ['/dashboard'];
}

const routes = loadRoutes();

test.describe('visual audit capture', () => {
  test.skip(!enabled, 'Set VISUAL_AUDIT=1 to run capture');

  test.use({
    viewport: { width: 1440, height: 900 },
  });

  test.beforeAll(() => {
    mkdirSync(outDir, { recursive: true });
  });

  for (const route of routes) {
    const slug = route.replace(/^\//, '').replace(/\//g, '__') || 'root';

    test(`capture ${route}`, async ({ page }) => {
      test.setTimeout(90_000);

      await login(page, undefined, undefined, { requireDashboard: false });
      await page.setViewportSize({ width: 1440, height: 900 });
      await page.goto(route, { waitUntil: 'domcontentloaded', timeout: 60_000 });

      await page.waitForLoadState('networkidle', { timeout: 15_000 }).catch(() => undefined);
      await page.waitForTimeout(500);

      const url = page.url();
      expect(url.includes('/login') && route !== '/login').toBeFalsy();

      const file = join(outDir, `${slug}.png`);
      await page.screenshot({ path: file, fullPage: false });
    });
  }
});
