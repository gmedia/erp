import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Approval Monitoring Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Authenticate using login helper
        await login(page, 'admin@dokfin.id', undefined, { requireDashboard: false });
    });

    test('can view approval monitoring dashboard and verify key elements', async ({ page }) => {
        await page.goto('/approval-monitoring');
        
        // Verify page title
        await expect(page).toHaveTitle(/Approval Monitoring/);
        
        // Check for main heading
        await expect(page.getByRole('heading', { name: 'Approval Monitoring' })).toBeVisible();

        // Check for Summary Cards
        await expect(page.locator('text=Pending Approvals')).toBeVisible();
        await expect(page.locator('text=Approved Today')).toBeVisible();
        await expect(page.locator('text=Rejected Today')).toBeVisible();
        await expect(page.locator('text=Avg Processing Time')).toBeVisible();
        
        // Check for Overdue Approvals list section
        await expect(page.locator('text=Overdue Approvals').first()).toBeVisible();

        // Since it's E2E, the page might show either "All caught up" or the data table
        const emptyStateText = page.locator('text=All caught up!');
        const tableHeader = page.getByRole('columnheader', { name: 'Document Type' });

        if (await emptyStateText.isVisible()) {
            await expect(emptyStateText).toBeVisible();
        } else if (await tableHeader.isVisible()) {
            await expect(tableHeader).toBeVisible();
            await expect(page.getByRole('columnheader', { name: 'Submitter' })).toBeVisible();
        }

        // Check if the combobox filter exists
        await expect(page.getByRole('combobox').first()).toBeVisible();
    });
});
