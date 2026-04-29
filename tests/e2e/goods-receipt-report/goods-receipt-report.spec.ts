import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import {
    createGoodsReceiptReportData,
    openGoodsReceiptReport,
    waitForGoodsReceiptReportResponse,
} from './helpers';

test.describe('Goods Receipt Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
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
            waitForGoodsReceiptReportResponse(page),
            filtersDialog.getByRole('button', { name: 'Apply Filters' }).click(),
        ]);

        await expect(page.locator('table')).toBeVisible();
    });
});
