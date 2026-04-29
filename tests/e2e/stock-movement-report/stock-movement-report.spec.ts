import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Stock Movement Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report, filter period, and export', async ({ page }) => {
        await page.goto('/reports/stock-movement');
        await page.waitForURL('**/reports/stock-movement', { timeout: 15000 });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-movement') &&
                    r.status() < 400,
                { timeout: 30000 },
            )
            ;

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-movement') &&
                    r.url().includes('sort_by=product_category_name') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: 'Category', exact: true }).click(),
        ]);

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-movement/export') &&
                    r.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const categoryFilter = filtersDialog
            .locator('button')
            .filter({ hasText: /All categories/i })
            .first();
        await categoryFilter.click({ force: true });

        const categoryOption = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(categoryOption).toBeVisible({ timeout: 10000 });
        await categoryOption.click({ force: true });

        await Promise.all([
            page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/reports/stock-movement') &&
                        r.status() < 400,
                )
                ,
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });
});
