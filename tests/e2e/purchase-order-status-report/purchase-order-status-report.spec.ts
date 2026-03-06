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
                response.url().includes('/reports/purchase-order-status') &&
                response.url().includes('sort_by=purchase_order_status_category') &&
                response.status() < 400,
        );
        await page
            .getByRole('button', {
                name: 'Status Category',
                exact: true,
            })
            .click();
        await sortResponsePromise;

        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/reports/purchase-order-status/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click();
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill(poNumber);
        await searchInput.press('Enter');
        await waitForPurchaseOrderStatusReportResponse(page);

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await waitForPurchaseOrderStatusReportResponse(page);

        await expect(page.locator('table')).toBeVisible();
    });
});
