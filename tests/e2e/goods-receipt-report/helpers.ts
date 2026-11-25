import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';
import { createGoodsReceipt } from '../goods-receipts/helpers';

export async function createGoodsReceiptReportData(page: Page): Promise<string> {
    return createGoodsReceipt(page);
}

export async function waitForGoodsReceiptReportResponse(
    page: Page,
): Promise<void> {
    await expectResponseOk(
        await page.waitForResponse((response) =>
            response.url().includes('/api/reports/goods-receipt'),
        ),
    );
}

export async function openGoodsReceiptReport(page: Page): Promise<void> {
    const initialResponse = page.waitForResponse((response) =>
        response.url().includes('/api/reports/goods-receipt'),
    );
    await page.goto('/reports/goods-receipt');
    await page.waitForURL('**/reports/goods-receipt', { timeout: 15000 });
    await expectResponseOk(await initialResponse);

    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('tbody tr').first()).toBeVisible();
}
