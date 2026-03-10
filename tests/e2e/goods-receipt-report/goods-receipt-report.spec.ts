import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    createGoodsReceiptReportData,
    openGoodsReceiptReport,
    waitForGoodsReceiptReportResponse,
} from './helpers';

test.describe('Goods Receipt Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view report, sort, filter, and export', async ({ page }) => {
        const grNumber = await createGoodsReceiptReportData(page);
        await openGoodsReceiptReport(page);

        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/reports/goods-receipt') &&
                response.url().includes('sort_by=supplier_name') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Supplier', exact: true }).click();
        await sortResponsePromise;

        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/reports/goods-receipt/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;

        const searchInput = page.getByRole('textbox').first();
        await searchInput.fill(grNumber);
        await searchInput.press('Enter');
        await waitForGoodsReceiptReportResponse(page);

        await page.getByRole('button', { name: /filters/i }).click();
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await waitForGoodsReceiptReportResponse(page);

        await expect(page.locator('table')).toBeVisible();
    });
});
