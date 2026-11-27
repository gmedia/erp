import { expect, test, type Page } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { login } from '../helpers';

const EVIDENCE_DIR = 'e2e/e2e-full/button-tabs';
const HEIGHT = 32;
const TEXT_SIZE = '13px';
const TEXT_WEIGHT = '600';

// Chrome melaporkan rounded-full sebagai angka besar (mis. 3.3e7px), bukan 9999px.
function isPill(radius: string, height: number): boolean {
    return parseFloat(radius) >= height / 2;
}

type ButtonStyle = {
    borderRadius: string;
    height: number;
    fontSize: string;
    fontWeight: string;
};

async function readButtonStyle(
    page: Page,
    selector: string,
): Promise<ButtonStyle> {
    return page
        .locator(selector)
        .first()
        .evaluate((el) => {
            const cs = getComputedStyle(el);
            return {
                borderRadius: cs.borderRadius,
                height: el.getBoundingClientRect().height,
                fontSize: cs.fontSize,
                fontWeight: cs.fontWeight,
            };
        });
}

for (const theme of ['light', 'dark'] as const) {
    test.describe(`button & tabs style (${theme})`, () => {
        test.beforeEach(async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            await page.addInitScript(() =>
                localStorage.setItem('app_locale', 'id'),
            );
            await login(page, undefined, undefined, {
                requireDashboard: true,
            });
        });

        test('tombol toolbar berbentuk pill, tinggi 32px, teks 13px semibold', async ({
            page,
        }) => {
            await page.goto('/employees', { waitUntil: 'domcontentloaded' });
            await page.waitForLoadState('networkidle').catch(() => undefined);

            const button = page
                .getByRole('button', { name: /tambah|add/i })
                .first();
            await expect(button).toBeVisible({ timeout: 15000 });

            const style = await readButtonStyle(
                page,
                'button:has-text("Tambah"), button:has-text("Add")',
            );

            expect(isPill(style.borderRadius, style.height)).toBe(true);
            expect(style.height).toBeCloseTo(HEIGHT, 0);
            expect(style.fontSize).toBe(TEXT_SIZE);
            expect(style.fontWeight).toBe(TEXT_WEIGHT);

            mkdirSync(EVIDENCE_DIR, { recursive: true });
            await page
                .locator('main')
                .first()
                .screenshot({ path: `${EVIDENCE_DIR}/toolbar-${theme}.png` });
        });

        test('track tabs pill bg-secondary dan item aktif sesuai tema', async ({
            page,
        }) => {
            await page.goto('/my-approvals', { waitUntil: 'domcontentloaded' });
            await page.waitForLoadState('networkidle').catch(() => undefined);

            const list = page.locator('[data-slot="tabs-list"]').first();
            await expect(list).toBeVisible({ timeout: 15000 });

            const active = page
                .locator('[data-slot="tabs-trigger"][data-state="active"]')
                .first();
            await expect(active).toBeVisible();

            const listStyle = await list.evaluate((el) => {
                const cs = getComputedStyle(el);
                const probe = document.createElement('span');
                probe.className = 'bg-secondary';
                document.body.appendChild(probe);
                const token = getComputedStyle(probe).backgroundColor;
                probe.remove();
                return {
                    radius: cs.borderRadius,
                    height: el.getBoundingClientRect().height,
                    background: cs.backgroundColor,
                    token,
                };
            });
            const activeStyle = await active.evaluate((el) => {
                const cs = getComputedStyle(el);
                return {
                    background: cs.backgroundColor,
                    shadow: cs.boxShadow,
                };
            });

            expect(isPill(listStyle.radius, listStyle.height)).toBe(true);
            expect(listStyle.height).toBeCloseTo(HEIGHT, 0);
            expect(listStyle.background).toBe(listStyle.token);

            const probeBg = await page.evaluate(
                (cls) => {
                    const probe = document.createElement('span');
                    probe.className = cls;
                    document.body.appendChild(probe);
                    const color = getComputedStyle(probe).backgroundColor;
                    probe.remove();
                    return color;
                },
                theme === 'dark' ? 'bg-accent' : 'bg-background',
            );
            expect(activeStyle.background).toBe(probeBg);
            expect(activeStyle.shadow).not.toBe('none');

            mkdirSync(EVIDENCE_DIR, { recursive: true });
            await page
                .locator('[data-slot="tabs"]')
                .first()
                .screenshot({ path: `${EVIDENCE_DIR}/tabs-${theme}.png` });
        });
    });
}
