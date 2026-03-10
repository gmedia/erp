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

        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/purchase-history') &&
                response.url().includes('sort_by=product_name') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Product', exact: true }).click({ force: true });
        await sortResponsePromise;

        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/purchase-history/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill(poNumber);
        await searchInput.press('Enter');
        await waitForPurchaseHistoryReportResponse(page);

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await waitForPurchaseHistoryReportResponse(page);

        await expect(page.locator('table')).toBeVisible();
    });
});
