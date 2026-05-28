import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { openApPaymentHistoryReport } from './helpers';

test.describe('AP Payment History Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report page', async ({ page }) => {
        await openApPaymentHistoryReport(page);
        await expect(page.locator('table')).toBeVisible();
    });

    test('can sort by column', async ({ page }) => {
        await openApPaymentHistoryReport(page);
        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ap-payment-history') &&
                response.url().includes('sort_by=') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Supplier', exact: true }).click({ force: true });
        await sortResponsePromise;
    });

    test('can export report', async ({ page }) => {
        await openApPaymentHistoryReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ap-payment-history/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;
    });

    test('can open filters dialog', async ({ page }) => {
        await openApPaymentHistoryReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
