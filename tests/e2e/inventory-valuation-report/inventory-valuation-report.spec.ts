import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Inventory Valuation Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, search, filter, and export', async ({ page }) => {
        await page.goto('/reports/inventory-valuation');
        await page.waitForURL('**/reports/inventory-valuation', { timeout: 15000 });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/reports/inventory-valuation') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const exportResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/reports/inventory-valuation/export') &&
                r.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click();
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill('P-');
        await searchInput.press('Enter');

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/reports/inventory-valuation') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
            )
            .catch(() => null);

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('button', { name: 'Apply Filters' }).click();

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/reports/inventory-valuation') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
            )
            .catch(() => null);
    });
});
