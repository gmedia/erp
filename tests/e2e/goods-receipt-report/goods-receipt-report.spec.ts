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

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/reports/goods-receipt') &&
                    response.url().includes('sort_by=supplier_name') &&
                    response.status() < 400,
            ),
            page.getByRole('button', { name: 'Supplier', exact: true }).click(),
        ]);

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/reports/goods-receipt/export') &&
                    response.status() < 400,
            ),
            page.getByRole('button', { name: /export/i }).click({ force: true }),
        ]);

        const searchInput = page.getByRole('textbox').first();
        await Promise.all([
            waitForGoodsReceiptReportResponse(page),
            searchInput.fill(grNumber).then(async () => {
                await searchInput.press('Enter');
            }),
        ]);

        await page.getByRole('button', { name: /filters/i }).click();
        await Promise.all([
            waitForGoodsReceiptReportResponse(page),
            page.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table')).toBeVisible();
    });
});
