import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Inventory Valuation Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report, search, filter, and export', async ({ page }) => {
        await page.goto('/reports/inventory-valuation');
        await page.waitForURL('**/reports/inventory-valuation', { timeout: 15000 });

        await page
            .waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/inventory-valuation') &&
                    r.request().headers()['accept']?.includes('application/json') &&
                    r.status() < 400,
            )
            ;

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/inventory-valuation/export') &&
                    r.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);

        const searchInput = page.getByRole('textbox').first();
        await Promise.all([
            page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/reports/inventory-valuation') &&
                        r.request().headers()['accept']?.includes(
                            'application/json',
                        ) &&
                        r.status() < 400,
                )
                ,
            searchInput.fill('P-').then(async () => {
                await searchInput.press('Enter');
            }),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const branchFilter = filtersDialog
            .locator('button')
            .filter({ hasText: /All branches/i })
            .first();
        await branchFilter.click({ force: true });

        const branchOption = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(branchOption).toBeVisible({ timeout: 10000 });
        await branchOption.click({ force: true });

        await Promise.all([
            page
                .waitForResponse(
                    (r) =>
                        r.url().includes('/api/reports/inventory-valuation') &&
                        r.request().headers()['accept']?.includes(
                            'application/json',
                        ) &&
                        r.status() < 400,
                )
                ,
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
    });
});
