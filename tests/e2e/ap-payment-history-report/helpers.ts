import { expect, Page } from '@playwright/test';

export async function waitForApPaymentHistoryReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/ap-payment-history') &&
            response.status() < 400,
    );
}

export async function openApPaymentHistoryReport(page: Page): Promise<void> {
    await page.goto('/reports/ap-payment-history');
    await page.waitForURL('**/reports/ap-payment-history', { timeout: 15000 });
    await waitForApPaymentHistoryReportResponse(page);
    await expect(page.locator('table')).toBeVisible();
}
