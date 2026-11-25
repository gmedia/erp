import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';
import { openCustomerStatementReport } from './helpers';

test.describe('Customer Statement Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report page', async ({ page }) => {
        await openCustomerStatementReport(page);
        await expect(page.locator('table')).toBeVisible();
    });

    test('can sort by column', async ({ page }) => {
        await openCustomerStatementReport(page);
        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'GET' &&
                response.url().includes('/api/reports/customer-statement') &&
                response.url().includes('sort_by='),
        );
        await page.getByRole('button', { name: 'Customer', exact: true }).click({ force: true });
        await expectResponseOk(await sortResponsePromise);
    });

    test('can export report', async ({ page }) => {
        await openCustomerStatementReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                response.url().includes('/api/reports/customer-statement/export'),
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await expectResponseOk(await exportResponsePromise);
    });

    test('can open filters dialog', async ({ page }) => {
        await openCustomerStatementReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
