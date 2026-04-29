import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Inventory Stocktake Variance Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report, sort, filter and export', async ({ page }) => {
        await page.goto('/reports/inventory-stocktake-variance');
        await page.waitForURL('**/reports/inventory-stocktake-variance', {
            timeout: 15000,
        });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/inventory-stocktake-variance') &&
                    r.status() < 400,
                { timeout: 30000 },
            )
            ;

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/inventory-stocktake-variance') &&
                    r.url().includes('sort_by=category_name') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: 'Category', exact: true }).click(),
        ]);

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes(
                        '/api/reports/inventory-stocktake-variance/export',
                    ) && r.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const resultFilter = filtersDialog
            .locator('button')
            .filter({ hasText: /All results/i })
            .first();
        await resultFilter.click({ force: true });

        const resultOption = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(resultOption).toBeVisible({ timeout: 10000 });
        await resultOption.click({ force: true });

        await Promise.all([
            page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/reports/inventory-stocktake-variance') &&
                        r.status() < 400,
                )
                ,
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });
});
