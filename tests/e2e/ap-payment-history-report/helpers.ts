import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';

export async function waitForApPaymentHistoryReportResponse(page: Page): Promise<void> {
    const response = await page.waitForResponse(
        (r) =>
            r.request().method() === 'GET' &&
            r.url().includes('/api/reports/ap-payment-history') &&
            !r.url().includes('/export'),
    );
    await expectResponseOk(response);
}

export async function openApPaymentHistoryReport(page: Page): Promise<void> {
    const responsePromise = waitForApPaymentHistoryReportResponse(page);
    await page.goto('/reports/ap-payment-history');
    await page.waitForURL('**/reports/ap-payment-history', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
