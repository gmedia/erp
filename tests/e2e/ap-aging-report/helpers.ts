import { expect, Page } from '@playwright/test';

export async function waitForApAgingReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/ap-aging') &&
            response.status() < 400,
    );
}

export async function openApAgingReport(page: Page): Promise<void> {
    await page.goto('/reports/ap-aging');
    await page.waitForURL('**/reports/ap-aging', { timeout: 15000 });
    await waitForApAgingReportResponse(page);
    await expect(page.locator('table')).toBeVisible();
}
