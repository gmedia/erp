import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';

export async function waitForApOutstandingReportResponse(page: Page): Promise<void> {
    const response = await page.waitForResponse(
        (r) =>
            r.request().method() === 'GET' &&
            r.url().includes('/api/reports/ap-outstanding') &&
            !r.url().includes('/export'),
    );
    await expectResponseOk(response);
}

export async function openApOutstandingReport(page: Page): Promise<void> {
    const responsePromise = waitForApOutstandingReportResponse(page);
    await page.goto('/reports/ap-outstanding');
    await page.waitForURL('**/reports/ap-outstanding', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
