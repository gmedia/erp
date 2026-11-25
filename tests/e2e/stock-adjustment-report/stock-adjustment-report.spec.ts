import { test, expect } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

test.describe('Stock Adjustment Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report, sort, filter, and export', async ({ page }) => {
        const initialResponsePromise = page.waitForResponse(
            (r) => r.url().includes('/api/reports/stock-adjustment'),
            { timeout: 30000 },
        );
        await page.goto('/reports/stock-adjustment');
        await page.waitForURL('**/reports/stock-adjustment', { timeout: 15000 });
        await expectResponseOk(await initialResponsePromise);

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const [sortResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-adjustment') &&
                    r.url().includes('sort_by=adjustment_type'),
                { timeout: 30000 },
            ),
            page
                .getByRole('button', { name: 'Adjustment Type', exact: true })
                .click(),
        ]);
        await expectResponseOk(sortResponse);

        const [exportResponse] = await Promise.all([
            page.waitForResponse((r) =>
                r.url().includes('/api/reports/stock-adjustment/export'),
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);
        await expectResponseOk(exportResponse);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const typeFilter = filtersDialog
            .locator('button')
            .filter({ hasText: /All types/i })
            .first();
        await typeFilter.click({ force: true });

        const typeOption = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(typeOption).toBeVisible({ timeout: 10000 });
        await typeOption.click({ force: true });

        const [filterResponse] = await Promise.all([
            page.waitForResponse((r) =>
                r.url().includes('/api/reports/stock-adjustment'),
            ),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);
        await expectResponseOk(filterResponse);
    });
});
