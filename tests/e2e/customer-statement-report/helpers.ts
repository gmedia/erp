import { expect, Page } from '@playwright/test';

export async function waitForCustomerStatementReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/customer-statement') &&
            response.status() < 400,
    );
}

export async function openCustomerStatementReport(page: Page): Promise<void> {
    await page.goto('/reports/customer-statement');
    await page.waitForURL('**/reports/customer-statement', { timeout: 15000 });
    await waitForCustomerStatementReportResponse(page);
    await expect(page.locator('table')).toBeVisible();
}
