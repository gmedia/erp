import { expect, Page } from '@playwright/test';
import { expectResponseOk } from '../helpers';

export async function waitForArAgingReportResponse(page: Page): Promise<void> {
    const response = await page.waitForResponse(
        (r) =>
            r.request().method() === 'GET' &&
            r.url().includes('/api/reports/ar-aging') &&
            !r.url().includes('/export'),
    );
    await expectResponseOk(response);
}

export async function openArAgingReport(page: Page): Promise<void> {
    const responsePromise = waitForArAgingReportResponse(page);
    await page.goto('/reports/ar-aging');
    await page.waitForURL('**/reports/ar-aging', { timeout: 15000 });
    await responsePromise;
    await expect(page.locator('table')).toBeVisible();
}
