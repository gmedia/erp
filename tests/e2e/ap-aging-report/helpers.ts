import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';

export async function waitForApAgingReportResponse(page: Page): Promise<void> {
    const response = await page.waitForResponse(
        (r) =>
            r.request().method() === 'GET' &&
            r.url().includes('/api/reports/ap-aging') &&
            !r.url().includes('/export'),
    );
    await expectResponseOk(response);
}

export async function openApAgingReport(page: Page): Promise<void> {
    const responsePromise = waitForApAgingReportResponse(page);
    await page.goto('/reports/ap-aging');
    await page.waitForURL('**/reports/ap-aging', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
