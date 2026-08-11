/**
 * Opt-in visual audit capture (VISUAL_AUDIT=1). Not part of default E2E.
 *
 * Prefer visual-audit config (no migrate:fresh):
 *   VISUAL_AUDIT=1 PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 \
 *     npx playwright test -c playwright.visual-audit.config.ts
 *
 * Route selection (first match wins):
 *   1. VISUAL_AUDIT_ROUTES=/dashboard,/departments   — comma-separated
 *   2. VISUAL_AUDIT_PRESET=shells|wave-0|exceptions|dashboards|smoke
 *      — named sets in docs/visual-audit/presets.json
 *   3. Full docs/visual-audit/url-list.json (85 routes) — avoid on low-RAM hosts
 *
 *   VISUAL_AUDIT_WAVE=wave-0   — PNG output dir under docs/visual-audit/waves/
 */
import { test, expect } from '@playwright/test';
import { mkdirSync, readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import { login } from '../helpers';

const enabled = process.env.VISUAL_AUDIT === '1';
const wave = process.env.VISUAL_AUDIT_WAVE ?? 'wave-0';
const outDir = join('docs/visual-audit/waves', wave);
const urlListPath = 'docs/visual-audit/url-list.json';
const presetsPath = 'docs/visual-audit/presets.json';

type PresetsFile = {
  presets?: Record<string, { routes?: string[]; note?: string }>;
};

function normalizeRoute(r: string): string {
  const t = r.trim();
  if (!t) {
    return '';
  }

  return t.startsWith('/') ? t : `/${t}`;
}

function parseCommaRoutes(raw: string): string[] {
  return raw
    .split(',')
    .map(normalizeRoute)
    .filter(Boolean);
}

function loadPresetRoutes(name: string): string[] {
  if (!existsSync(presetsPath)) {
    throw new Error(
      `VISUAL_AUDIT_PRESET=${name} set but ${presetsPath} is missing`,
    );
  }

  const file = JSON.parse(readFileSync(presetsPath, 'utf8')) as PresetsFile;
  const preset = file.presets?.[name];
  if (!preset?.routes?.length) {
    const known = Object.keys(file.presets ?? {}).join(', ') || '(none)';
    throw new Error(
      `Unknown VISUAL_AUDIT_PRESET="${name}". Known: ${known}`,
    );
  }

  return preset.routes.map(normalizeRoute).filter(Boolean);
}

function loadFullUrlList(): string[] {
  if (!existsSync(urlListPath)) {
    return ['/dashboard'];
  }

  const raw = JSON.parse(readFileSync(urlListPath, 'utf8')) as {
    routes?: string[];
  };

  return raw.routes?.length ? raw.routes : ['/dashboard'];
}

function loadRoutes(): string[] {
  const override = process.env.VISUAL_AUDIT_ROUTES?.trim();
  if (override) {
    return parseCommaRoutes(override);
  }

  const preset = process.env.VISUAL_AUDIT_PRESET?.trim();
  if (preset) {
    return loadPresetRoutes(preset);
  }

  return loadFullUrlList();
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
