import { expect, test } from '@playwright/test';
import { login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('issue 597 i18n polish (locale id)', () => {
    test.beforeEach(async ({ page }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);
    });

    test('products type badges use Indonesian labels', async ({ page }) => {
        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        await expect(page.getByText('Finished Good')).toHaveCount(0);
        await expect(page.getByText('Raw Material')).toHaveCount(0);
        await expect(page.getByText('Bahan Baku').first()).toBeVisible({
            timeout: 15000,
        });
    });

    test('general ledger headers are localized', async ({ page }) => {
        await login(page);
        await page.goto('/reports/general-ledger', {
            waitUntil: 'domcontentloaded',
        });

        await expect(page.getByText('Tanggal').first()).toBeVisible({
            timeout: 15000,
        });
        await expect(page.getByText('No. Jurnal').first()).toBeVisible();
        await expect(page.getByText('Saldo Berjalan').first()).toBeVisible();
        await expect(page.getByText('Entry Number')).toHaveCount(0);
        await expect(page.getByText('Running Balance')).toHaveCount(0);
    });

    test('appearance settings heading is localized', async ({ page }) => {
        await login(page);
        await page.goto('/settings/appearance', {
            waitUntil: 'domcontentloaded',
        });

        await expect(
            page.getByRole('heading', { name: 'Pengaturan tampilan' }),
        ).toBeVisible({ timeout: 15000 });
        await expect(page.getByText('Appearance settings')).toHaveCount(0);
    });

    test('toolbar secondary buttons share the same filled variant', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        const filter = page.getByTestId('filter-button');
        await expect(filter).toBeVisible({ timeout: 15000 });
        const fill = await filter.evaluate(
            (el) => getComputedStyle(el).backgroundColor,
        );
        expect(fill).not.toBe('rgba(0, 0, 0, 0)');

        for (const testId of ['export-button', 'columns-button']) {
            const button = page.getByTestId(testId);
            await expect(button).toBeVisible();
            await expect(button).toHaveCSS('background-color', fill);
        }
    });

    test('pagination rows-per-page select has no outline border', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/products', { waitUntil: 'domcontentloaded' });

        const trigger = page
            .locator('[data-slot="select-trigger"]')
            .filter({ hasText: /^(10|15|25|50|100)$/ })
            .first();
        await expect(trigger).toBeVisible({ timeout: 15000 });
        await expect(trigger).toHaveCSS('border-top-width', '1px');
        await expect(trigger).toHaveCSS('border-top-color', 'rgba(0, 0, 0, 0)');
    });
});
