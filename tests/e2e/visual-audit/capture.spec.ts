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
 *
 * Matrix (optional, default light + desktop → `<slug>.png`):
 *   VISUAL_AUDIT_THEMES=light,dark          — via prefers-color-scheme (appearance "system")
 *   VISUAL_AUDIT_VIEWPORTS=desktop,mobile   — 1440×900 / 390×844
 *   Non-default combos → `<slug>--<theme>--<viewport>.png`. Per-capture metrics
 *   (horizontal overflow, console errors) → `<outDir>/report.jsonl`.
 */
import { expect, test } from '@playwright/test';
import { appendFileSync, existsSync, mkdirSync, readFileSync } from 'node:fs';
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
    return raw.split(',').map(normalizeRoute).filter(Boolean);
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

type Theme = 'light' | 'dark';
type ViewportName = 'desktop' | 'mobile';

const VIEWPORTS: Record<ViewportName, { width: number; height: number }> = {
    desktop: { width: 1440, height: 900 },
    mobile: { width: 390, height: 844 },
};

function parseList<T extends string>(
    raw: string | undefined,
    allowed: readonly T[],
    fallback: T,
): T[] {
    const items = (raw ?? '')
        .split(',')
        .map((v) => v.trim())
        .filter((v): v is T => (allowed as readonly string[]).includes(v));

    return items.length ? items : [fallback];
}

const themes = parseList<Theme>(
    process.env.VISUAL_AUDIT_THEMES,
    ['light', 'dark'],
    'light',
);
const viewports = parseList<ViewportName>(
    process.env.VISUAL_AUDIT_VIEWPORTS,
    ['desktop', 'mobile'],
    'desktop',
);
const reportPath = join(outDir, 'report.jsonl');

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

            const consoleErrors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    consoleErrors.push(msg.text().slice(0, 300));
                }
            });

            await login(page, undefined, undefined, { requireDashboard: true });
            await page.setViewportSize(VIEWPORTS.desktop);

            const gotoTarget = async (): Promise<void> => {
                await page.goto(route, {
                    waitUntil: 'domcontentloaded',
                    timeout: 60_000,
                });
                await page
                    .waitForLoadState('networkidle', { timeout: 15_000 })
                    .catch(() => undefined);
                // Harness screenshot: jeda render stabil (animasi/paint) sebelum capture; tidak ada elemen tunggal yang menandai kesiapan.
                await page.waitForTimeout(500);
            };

            await gotoTarget();

            if (route !== '/login' && page.url().includes('/login')) {
                await login(page, undefined, undefined, {
                    requireDashboard: true,
                });
                await gotoTarget();
            }

            const url = page.url();
            expect(url.includes('/login') && route !== '/login').toBeFalsy();

            for (const viewport of viewports) {
                await page.setViewportSize(VIEWPORTS[viewport]);

                for (const theme of themes) {
                    await page.emulateMedia({ colorScheme: theme });
                    // Harness screenshot: jeda transisi tema (CSS) sebelum metrik dan capture.
                    await page.waitForTimeout(300);

                    const metrics = await page.evaluate(() => ({
                        dark: document.documentElement.classList.contains(
                            'dark',
                        ),
                        scrollWidth: document.documentElement.scrollWidth,
                        innerWidth: window.innerWidth,
                    }));

                    const isDefault =
                        theme === 'light' && viewport === 'desktop';
                    const file = join(
                        outDir,
                        isDefault
                            ? `${slug}.png`
                            : `${slug}--${theme}--${viewport}.png`,
                    );
                    await page.screenshot({ path: file, fullPage: false });

                    const clippedCells = await page.evaluate(() => {
                        const edgeFadeWidthPx = 16;
                        const out: string[] = [];
                        document
                            .querySelectorAll<HTMLElement>(
                                '[data-slot="table-container"]',
                            )
                            .forEach((container) => {
                                const row = container.querySelector(
                                    'tbody tr[data-state], tbody tr',
                                );
                                if (!row) return;
                                const containerRight =
                                    container.getBoundingClientRect().right;
                                const fadeLeft = container.hasAttribute(
                                    'data-overflow-right',
                                )
                                    ? containerRight - edgeFadeWidthPx
                                    : containerRight;
                                const cells = Array.from(
                                    row.querySelectorAll<HTMLElement>(
                                        ':scope > td',
                                    ),
                                );
                                let stickyLeft = Infinity;
                                for (const cell of cells) {
                                    if (
                                        getComputedStyle(cell).position ===
                                        'sticky'
                                    ) {
                                        const cellLeft =
                                            cell.getBoundingClientRect().left;
                                        const pseudo = getComputedStyle(
                                            cell,
                                            '::before',
                                        );
                                        const hasPseudo =
                                            pseudo.content !== 'none' &&
                                            pseudo.content !== '';
                                        const pseudoLeft = hasPseudo
                                            ? cellLeft +
                                              (parseFloat(pseudo.left) || 0)
                                            : cellLeft;
                                        stickyLeft = Math.min(
                                            stickyLeft,
                                            pseudoLeft,
                                        );
                                    }
                                }
                                const limit = Math.min(fadeLeft, stickyLeft);
                                for (const cell of cells) {
                                    if (
                                        getComputedStyle(cell).position ===
                                        'sticky'
                                    ) {
                                        continue;
                                    }
                                    const range = document.createRange();
                                    range.selectNodeContents(cell);
                                    const textRight =
                                        range.getBoundingClientRect().right;
                                    const text = (cell.textContent ?? '')
                                        .trim()
                                        .slice(0, 40);
                                    if (text && textRight > limit + 0.5) {
                                        out.push(
                                            `${text} (text right ${Math.round(textRight)} > limit ${Math.round(limit)})`,
                                        );
                                    }
                                }
                            });
                        return out;
                    });

                    appendFileSync(
                        reportPath,
                        JSON.stringify({
                            route,
                            theme,
                            viewport,
                            file,
                            darkClass: metrics.dark,
                            horizontalOverflow: Math.max(
                                0,
                                metrics.scrollWidth - metrics.innerWidth,
                            ),
                            consoleErrors: [...consoleErrors],
                            clippedCells,
                        }) + '\n',
                    );
                }
            }
        });
    }
});
