import { expect, test } from '@playwright/test';
import { login } from '../helpers';
import { openArOutstandingReport } from './helpers';

test.describe('AR Outstanding Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report page', async ({ page }) => {
        await openArOutstandingReport(page);
        await expect(page.locator('table')).toBeVisible();
    });

    test('can sort by column', async ({ page }) => {
        await openArOutstandingReport(page);
        const sortResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ar-outstanding') &&
                response.url().includes('sort_by=') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Customer', exact: true }).click({ force: true });
        await sortResponsePromise;
    });

    test('can export report', async ({ page }) => {
        await openArOutstandingReport(page);
        const exportResponsePromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/ar-outstanding/export') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: /export/i }).click({ force: true });
        await exportResponsePromise;
    });

    test('can open filters dialog', async ({ page }) => {
        await openArOutstandingReport(page);
        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();
        await expect(filtersDialog.getByRole('button', { name: 'Apply Filters' })).toBeVisible();
    });
});
