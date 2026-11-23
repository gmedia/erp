import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { openArAgingReport } from './helpers';

test.describe('AR Aging Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report page', async ({ page }) => {
        await openArAgingReport(page);
        await expect(page.locator('table')).toBeVisible();
    });

    test('shows total row with as-of date below the table', async ({ page }) => {
        await openArAgingReport(page);
        const summary = page.getByTestId('report-summary');
        await expect(summary).toBeVisible();
        await expect(page.getByTestId('report-as-of')).toContainText('As of ');
        await expect(page.getByTestId('report-total-amount_due')).toBeVisible();
        await expect(page.getByTestId('report-total-aging_over_90')).toBeVisible();
    });

    test('can sort by column', async ({ page }) => {
        await openArAgingReport(page);
        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ar-aging') &&
                response.url().includes('sort_by=') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Customer', exact: true }).click({ force: true });
        await sortResponsePromise;
    });

    test('can export report', async ({ page }) => {
        await openArAgingReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ar-aging/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;
    });

    test('can open filters dialog', async ({ page }) => {
        await openArAgingReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
