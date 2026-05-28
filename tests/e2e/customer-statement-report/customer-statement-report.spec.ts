import { expect, test } from '@playwright/test';
import { login } from '../helpers';
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
                response.url().includes('/api/reports/customer-statement') &&
                response.url().includes('sort_by=') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Customer', exact: true }).click({ force: true });
        await sortResponsePromise;
    });

    test('can export report', async ({ page }) => {
        await openCustomerStatementReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/customer-statement/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;
    });

    test('can open filters dialog', async ({ page }) => {
        await openCustomerStatementReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
