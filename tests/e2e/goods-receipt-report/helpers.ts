import { expect, Page } from '@playwright/test';
import { createGoodsReceipt } from '../goods-receipts/helpers';

export async function createGoodsReceiptReportData(page: Page): Promise<string> {
    return createGoodsReceipt(page);
}

export async function waitForGoodsReceiptReportResponse(
    page: Page,
): Promise<void> {
    await page
        .waitForResponse(
            (response) =>
                response.url().includes('/api/reports/goods-receipt') &&
                response.status() < 400,
        )
        ;
}

export async function openGoodsReceiptReport(page: Page): Promise<void> {
    await page.goto('/reports/goods-receipt');
    await page.waitForURL('**/reports/goods-receipt', { timeout: 15000 });
    await waitForGoodsReceiptReportResponse(page);

    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('tbody tr').first()).toBeVisible();
}
