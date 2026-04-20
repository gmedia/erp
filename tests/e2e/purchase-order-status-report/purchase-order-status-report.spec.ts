import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    createPurchaseOrderReportData,
    openPurchaseOrderStatusReport,
    waitForPurchaseOrderStatusReportResponse,
} from './helpers';

test.describe('Purchase Order Status Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, sort, filter, and export', async ({ page }) => {
        const poNumber = await createPurchaseOrderReportData(page);
        await openPurchaseOrderStatusReport(page);

        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/purchase-order-status') &&
                response.url().includes('sort_by=purchase_order_status_category') &&
                response.status() < 400,
        );
        await page
            .getByRole('button', {
                name: 'Status Category',
                exact: true,
            })
            .click({ force: true });
        await sortResponsePromise;

        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/purchase-order-status/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await Promise.all([
            waitForPurchaseOrderStatusReportResponse(page),
            searchInput.fill(poNumber).then(async () => {
                await searchInput.press('Enter');
            }),
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
            waitForPurchaseOrderStatusReportResponse(page),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table')).toBeVisible();
    });
});
