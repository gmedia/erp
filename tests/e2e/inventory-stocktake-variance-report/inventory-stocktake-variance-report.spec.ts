import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Inventory Stocktake Variance Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, sort, filter and export', async ({ page }) => {
        await page.goto('/reports/inventory-stocktake-variance');
        await page.waitForURL('**/reports/inventory-stocktake-variance', {
            timeout: 15000,
        });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/reports/inventory-stocktake-variance') &&
                    r.request().headers()['accept']?.includes(
                        'application/json',
                    ) &&
                    r.status() < 400,
            )
            .catch(() => null);

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const sortResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes('/reports/inventory-stocktake-variance') &&
                r.url().includes('sort_by=category_name') &&
                r.status() < 400,
        );
        await page.getByRole('button', { name: 'Category', exact: true }).click();
        await sortResponsePromise;

        const exportResponsePromise = page.waitForResponse(
            (r) =>
                r.url().includes(
                    '/reports/inventory-stocktake-variance/export',
                ) && r.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click();
        await exportResponsePromise;

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('button', { name: 'Apply Filters' }).click();

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/reports/inventory-stocktake-variance') &&
                    r.request().headers()['accept']?.includes(
                        'application/json',
                    ) &&
                    r.status() < 400,
            )
            .catch(() => null);
    });
});
