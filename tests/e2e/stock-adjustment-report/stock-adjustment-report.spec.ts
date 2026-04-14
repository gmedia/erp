import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Stock Adjustment Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, sort, filter, and export', async ({ page }) => {
        await page.goto('/reports/stock-adjustment');
        await page.waitForURL('**/reports/stock-adjustment', { timeout: 15000 });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-adjustment') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
                { timeout: 30000 },
            )
            .catch(() => null);

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-adjustment') &&
                    r.url().includes('sort_by=adjustment_type') &&
                    r.status() < 400,
                { timeout: 30000 },
            ),
            page
                .getByRole('button', { name: 'Adjustment Type', exact: true })
                .click(),
        ]);

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-adjustment/export') &&
                    r.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        await Promise.all([
            page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/reports/stock-adjustment') &&
                        r.request().headers()['accept']?.includes(
                            'application/json',
                        ) &&
                        r.status() < 400,
                )
                .catch(() => null),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });
});
