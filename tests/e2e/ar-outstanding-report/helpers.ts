import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';

export async function waitForArOutstandingReportResponse(page: Page): Promise<void> {
    const response = await page.waitForResponse(
        (r) =>
            r.request().method() === 'GET' &&
            r.url().includes('/api/reports/ar-outstanding') &&
            !r.url().includes('/export'),
    );
    await expectResponseOk(response);
}

export async function openArOutstandingReport(page: Page): Promise<void> {
    const responsePromise = waitForArOutstandingReportResponse(page);
    await page.goto('/reports/ar-outstanding');
    await page.waitForURL('**/reports/ar-outstanding', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
