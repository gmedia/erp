import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';
import { openApOutstandingReport } from './helpers';

test.describe('AP Outstanding Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report page', async ({ page }) => {
        await openApOutstandingReport(page);
        await expect(page.locator('table')).toBeVisible();
    });

    test('can sort by column', async ({ page }) => {
        await openApOutstandingReport(page);
        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                response.url().includes('/api/reports/ap-outstanding') &&
                response.url().includes('sort_by='),
        );
        await page.getByRole('button', { name: 'Supplier', exact: true }).click({ force: true });
        await expectResponseOk(await sortResponsePromise);
    });

    test('can export report', async ({ page }) => {
        await openApOutstandingReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                response.url().includes('/api/reports/ap-outstanding/export'),
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await expectResponseOk(await exportResponsePromise);
    });

    test('can open filters dialog', async ({ page }) => {
        await openApOutstandingReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
