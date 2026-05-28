import { expect, Page } from '@playwright/test';

export async function waitForArAgingReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/ar-aging') &&
            response.status() < 400,
    );
}

export async function openArAgingReport(page: Page): Promise<void> {
    await page.goto('/reports/ar-aging');
    await page.waitForURL('**/reports/ar-aging', { timeout: 15000 });
    await waitForArAgingReportResponse(page);
    await expect(page.locator('table')).toBeVisible();
}
