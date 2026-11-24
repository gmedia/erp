import { expect, Page } from '@playwright/test';

export async function waitForArOutstandingReportResponse(page: Page): Promise<void> {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/api/reports/ar-outstanding') &&
            response.status() < 400,
    );
}

export async function openArOutstandingReport(page: Page): Promise<void> {
    const responsePromise = waitForArOutstandingReportResponse(page);
    await page.goto('/reports/ar-outstanding');
    await page.waitForURL('**/reports/ar-outstanding', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
