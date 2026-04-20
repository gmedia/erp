import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    createPurchaseHistoryReportData,
    openPurchaseHistoryReport,
    waitForPurchaseHistoryReportResponse,
} from './helpers';

test.describe('Purchase History Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, sort, filter, and export', async ({ page }) => {
        const poNumber = await createPurchaseHistoryReportData(page);
        await openPurchaseHistoryReport(page);

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/reports/purchase-history') &&
                    response.url().includes('sort_by=product_name') &&
                    response.status() < 400,
            ),
            page
                .getByRole('button', { name: 'Product', exact: true })
                .click({ force: true }),
        ]);

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/reports/purchase-history/export') &&
                    response.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click({ force: true }),
        ]);

        const searchInput = page.getByRole('textbox').first();
        await Promise.all([
            waitForPurchaseHistoryReportResponse(page),
            searchInput.fill(poNumber).then(async () => {
                await searchInput.press('Enter');
            }),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const statusFilter = filtersDialog
            .locator('button')
            .filter({ hasText: /All statuses/i })
            .first();
        await statusFilter.click({ force: true });

        const statusOption = page
            .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
            .first();
        await expect(statusOption).toBeVisible({ timeout: 10000 });
        await statusOption.click({ force: true });

        await Promise.all([
            waitForPurchaseHistoryReportResponse(page),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table')).toBeVisible();
    });
});
