import { expect, test, type Page } from '@playwright/test';
import { mkdirSync, writeFileSync } from 'node:fs';
import { login } from '../helpers';

const EVIDENCE_DIR = 'e2e/e2e-full/form-control';

/**
 * Guard gaya kontrol teks (#582): input, textarea, dan kolom search toolbar
 * harus memakai spesifikasi referensi — tinggi 32px, border transparan
 * saat diam, tanpa shadow, dan ring fokus 2px netral saat focus-visible.
 */

const RADIUS_INPUT = '6px';
const RADIUS_SEARCH = '8px';
const HEIGHT = 32;

type ControlStyle = {
    borderRadius: string;
    height: number;
    borderColor: string;
    borderWidth: string;
    boxShadow: string;
};

async function readControlStyle(
    page: Page,
    selector: string,
): Promise<ControlStyle> {
    return page
        .locator(selector)
        .first()
        .evaluate((el) => {
            const cs = getComputedStyle(el);
            return {
                borderRadius: cs.borderRadius,
                height: el.getBoundingClientRect().height,
                borderColor: cs.borderTopColor,
                borderWidth: cs.borderTopWidth,
                boxShadow: cs.boxShadow,
            };
        });
}

async function openAddEmployeeDialog(page: Page): Promise<void> {
    await page.goto('/employees', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle').catch(() => undefined);
    await page
        .getByRole('button', { name: /tambah|add/i })
        .first()
        .click();
    const dialog = page.getByRole('dialog').first();
    await expect(dialog).toBeVisible({ timeout: 15000 });
    await dialog.evaluate(async (node) => {
        await Promise.all(node.getAnimations().map((a) => a.finished));
    });
}

for (const theme of ['light', 'dark'] as const) {
    test.describe(`form control style (${theme})`, () => {
        test.beforeEach(async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            await page.addInitScript(() =>
                localStorage.setItem('app_locale', 'id'),
            );
            await login(page, undefined, undefined, {
                requireDashboard: true,
            });
        });

        test('input dialog memakai radius 6px, tinggi 32px, dan border transparan', async ({
            page,
        }) => {
            await openAddEmployeeDialog(page);

            // Radix memfokuskan field pertama saat dialog dibuka; pindahkan fokus
            // ke judul dan tunggu transisi box-shadow fokus awal selesai.
            await page
                .getByRole('dialog')
                .first()
                .getByRole('heading')
                .first()
                .click();
            await page.waitForTimeout(800);
            const rest = await page.evaluate(() => {
                const node = document.activeElement as HTMLElement | null;
                return {
                    tag: node?.tagName ?? '',
                    type: node?.getAttribute('type') ?? '',
                    focusVisible: node?.matches(':focus-visible') ?? false,
                };
            });
            console.log(
                `[form-control] rest-focus ${theme}: ${JSON.stringify(rest)}`,
            );
            const focusedField = await page.evaluate(() =>
                document.activeElement?.matches(
                    '[role="dialog"] input[type="text"]',
                ),
            );
            expect(focusedField).toBe(false);

            const input = await readControlStyle(
                page,
                '[role="dialog"] input[type="text"]',
            );
            mkdirSync(EVIDENCE_DIR, { recursive: true });
            await page
                .getByRole('dialog')
                .first()
                .screenshot({
                    path: `${EVIDENCE_DIR}/dialog-rest-${theme}.png`,
                });

            expect(input.borderRadius).toBe(RADIUS_INPUT);
            expect(input.height).toBeCloseTo(HEIGHT, 0);
            expect(input.borderWidth).toBe('1px');
            expect(input.borderColor).toBe('rgba(0, 0, 0, 0)');
            expect(input.boxShadow).toBe('none');
        });

        test('kolom search toolbar memakai radius 8px dan tinggi 32px', async ({
            page,
        }) => {
            await page.goto('/employees', { waitUntil: 'domcontentloaded' });

            await page.evaluate(() =>
                (document.activeElement as HTMLElement | null)?.blur(),
            );
            const search = await readControlStyle(
                page,
                '[data-testid="search-input"]',
            );

            expect(search.borderRadius).toBe(RADIUS_SEARCH);
            expect(search.height).toBeCloseTo(HEIGHT, 0);
            expect(search.borderColor).toBe('rgba(0, 0, 0, 0)');
            expect(search.boxShadow).toBe('none');
        });

        test('trigger select, datepicker, dan async select memakai gaya referensi (#583)', async ({
            page,
        }) => {
            await openAddEmployeeDialog(page);

            const triggers = {
                select: '[role="dialog"] button[role="combobox"]',
                datepicker:
                    '[role="dialog"] button[aria-haspopup="dialog"]:has(svg.lucide-calendar)',
                asyncSelect:
                    '[role="dialog"] button[role="combobox"]:not([data-slot="select-trigger"])',
            };
            const selectStyle = await readControlStyle(page, triggers.select);
            const dateStyle = await readControlStyle(page, triggers.datepicker);
            const asyncStyle = await readControlStyle(
                page,
                triggers.asyncSelect,
            );

            mkdirSync(EVIDENCE_DIR, { recursive: true });
            await page
                .locator(triggers.datepicker)
                .first()
                .scrollIntoViewIfNeeded();
            await page
                .getByRole('dialog')
                .first()
                .screenshot({
                    path: `${EVIDENCE_DIR}/select-date-${theme}.png`,
                });
            await page
                .locator(triggers.datepicker)
                .first()
                .screenshot({
                    path: `${EVIDENCE_DIR}/datepicker-trigger-${theme}.png`,
                });

            for (const style of [selectStyle, dateStyle, asyncStyle]) {
                expect(style.borderRadius).toBe(RADIUS_INPUT);
                expect(style.height).toBeCloseTo(HEIGHT, 0);
                expect(style.borderWidth).toBe('1px');
                expect(style.borderColor).toBe('rgba(0, 0, 0, 0)');
                expect(style.boxShadow).toBe('none');
            }
        });

        test('input mendapat ring fokus 2px netral saat focus-visible', async ({
            page,
        }) => {
            await openAddEmployeeDialog(page);

            const dialog = page.getByRole('dialog').first();
            await dialog.getByRole('heading').first().click();
            await page.waitForTimeout(800);
            const fieldLocator = page
                .locator('[role="dialog"] input[type="text"]')
                .first();
            const restPng = await fieldLocator.screenshot();
            await page.keyboard.press('Tab');
            await page.waitForTimeout(800);

            const focused = await page.evaluate(() => {
                const node = document.activeElement as HTMLElement | null;
                if (!node)
                    return { isField: false, visible: false, shadow: '' };
                return {
                    isField: node.matches('[role="dialog"] input[type="text"]'),
                    visible: node.matches(':focus-visible'),
                    shadow: getComputedStyle(node).boxShadow,
                };
            });
            console.log(
                `[form-control] focus ${theme}: ${JSON.stringify(focused)}`,
            );
            expect(focused.isField).toBe(true);
            expect(focused.visible).toBe(true);
            expect(focused.shadow).toContain('2px');
            mkdirSync(EVIDENCE_DIR, { recursive: true });
            const focusPng = await fieldLocator.screenshot();
            writeFileSync(`${EVIDENCE_DIR}/field-rest-${theme}.png`, restPng);
            writeFileSync(`${EVIDENCE_DIR}/field-focus-${theme}.png`, focusPng);
            await page
                .getByRole('dialog')
                .first()
                .screenshot({
                    path: `${EVIDENCE_DIR}/dialog-focus-${theme}.png`,
                });
        });
    });
}
