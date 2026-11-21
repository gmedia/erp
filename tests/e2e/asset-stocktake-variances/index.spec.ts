import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Stocktake Variance Report E2E', () => {
    test.beforeEach(async ({ page }) => {
        // We use admin to have permission
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('it can view the variance dashboard and filter data', async ({ page }) => {
        // Go to variance report page
        await page.goto('/reports/asset-stocktake-variances');
        
        // Ensure page loaded properly
        // Data table should be visible
        await expect(page.locator('table')).toBeVisible();

        // Toggle filter panel
        await page.getByRole('button', { name: 'Filters' }).click();

        // Check the different filter options exist inside the dialog
        const dialog = page.getByRole('dialog');
        await expect(dialog.getByText('Result', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Branch', { exact: true })).toBeVisible();
        await expect(dialog.getByText('Stocktake', { exact: true })).toBeVisible();

        // Apply filters to close dialog
        await dialog.getByRole('button', { name: 'Apply Filters' }).click();

        // Pagination info must reflect visible rows, not "0 to 0" (W4-20)
        const pagination = page.getByText(/Showing \d+ to \d+ of \d+/);
        await expect(pagination).toBeVisible();
        await expect(pagination).not.toHaveText(/Showing 0 to 0 of 1/);

        // Should be able to export
        const exportBtn = page.getByRole('button', { name: 'Export', exact: true });
        await expect(exportBtn).toBeVisible();
        
        // Set search text
        await page.getByPlaceholder('Search code, name, notes...').fill('Damaged Asset');

        // Note: the test just verifies the UI wiring works and doesn't crash. 
        // Real testing happens in feature tests.
    });
});
