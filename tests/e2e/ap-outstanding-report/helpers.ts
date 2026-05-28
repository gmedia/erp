import { expect, Page } from '@playwright/test';

export async function waitForApOutstandingReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/ap-outstanding') &&
            response.status() < 400,
    );
}

export async function openApOutstandingReport(page: Page): Promise<void> {
    await page.goto('/reports/ap-outstanding');
    await page.waitForURL('**/reports/ap-outstanding', { timeout: 15000 });
    await waitForApOutstandingReportResponse(page);
    await expect(page.locator('table')).toBeVisible();
}
