import { expect, test, type Page } from '@playwright/test';
import { login } from '../helpers';

/**
 * Guard gaya trigger select & datepicker (#583): trigger harus memakai
 * spesifikasi referensi — tinggi 32px, radius 6px (rounded-md), border
 * transparan saat diam, tanpa shadow, dan ring fokus 2px netral saat
 * focus-visible.
 */

const RADIUS_CONTROL = '6px';
const HEIGHT = 32;

type TriggerStyle = {
    borderRadius: string;
    height: number;
    borderColor: string;
    borderWidth: string;
    boxShadow: string;
};

async function readTriggerStyle(
    page: Page,
    selector: string,
): Promise<TriggerStyle> {
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
    test.describe(`select & datepicker style (${theme})`, () => {
        test.beforeEach(async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            await page.addInitScript(() =>
                localStorage.setItem('app_locale', 'id'),
            );
            await login(page, undefined, undefined, {
                requireDashboard: true,
            });
        });

        test('trigger select di dialog memakai radius 6px, tinggi 32px, dan border transparan', async ({
            page,
        }) => {
            await openAddEmployeeDialog(page);

            const trigger = await readTriggerStyle(
                page,
                '[role="dialog"] button[role="combobox"]',
            );

            expect(trigger.borderRadius).toBe(RADIUS_CONTROL);
            expect(trigger.height).toBeCloseTo(HEIGHT, 0);
            expect(trigger.borderWidth).toBe('1px');
            expect(trigger.borderColor).toBe('rgba(0, 0, 0, 0)');
            expect(trigger.boxShadow).toBe('none');
        });

        test('trigger select mendapat ring fokus 2px netral saat focus-visible', async ({
            page,
        }) => {
            await openAddEmployeeDialog(page);

            const dialog = page.getByRole('dialog').first();
            await dialog.getByRole('heading').first().click();
            await page.waitForTimeout(800);
            const trigger = page
                .locator('[role="dialog"] button[role="combobox"]')
                .first();
            await trigger.focus();
            await page.keyboard.press('Shift+Tab');
            await page.keyboard.press('Tab');
            await page.waitForTimeout(800);

            const focused = await page.evaluate(() => {
                const node = document.activeElement as HTMLElement | null;
                if (!node) return { isTrigger: false, shadow: '' };
                return {
                    isTrigger:
                        node.matches('[role="dialog"] button[role="combobox"]'),
                    shadow: getComputedStyle(node).boxShadow,
                };
            });
            expect(focused.isTrigger).toBe(true);
            expect(focused.shadow).toContain('2px');
        });
    });
}
