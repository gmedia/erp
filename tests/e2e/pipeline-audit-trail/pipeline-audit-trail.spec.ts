import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Pipeline Audit Trail', () => {
    test.beforeEach(async ({ page }) => {
        // Log in to gain session
        await login(page);
    });

    test('can view pipeline audit trail list and open detail modal', async ({ page }) => {
        // Ensure we hit the dashboard first
        await expect(page).toHaveURL(/.*dashboard/);
        
        // 1. Navigate to Pipeline Audit Trail page
        await page.goto('/pipeline-audit-trail');
        await page.waitForURL('**/pipeline-audit-trail', { timeout: 15000 });
        
        // 2. Verify page header
        await expect(page.getByText('Pipeline Audit Trail').last()).toBeVisible();

        // 3. Verify that the table renders (wait for API data to load)
        const table = page.locator('table');
        await expect(table).toBeVisible();
        await expect(page.getByText('Loading...')).not.toBeVisible();
        
        // Assuming there is seeded initial data from earlier tests or we just check table headers
        await expect(page.getByRole('columnheader', { name: 'Date' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Pipeline' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Entity Type' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'From State' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'To State' })).toBeVisible();

        // 4. Test filter presence
        await expect(page.getByPlaceholder('Search entity ID, performer, comment...')).toBeVisible();
        
        // Open the filters collapsible if it's not open
        const filtersButton = page.getByRole('button', { name: 'Filters' });
        await expect(filtersButton).toBeVisible();
        await filtersButton.click();
        await page.waitForTimeout(500); // Wait for transition
        
        const filterDialog = page.getByRole('dialog').or(page.locator('.filter-container').first());
        await expect(page.getByText('Entity Type').last()).toBeVisible();
        await expect(page.getByText('Pipeline').last()).toBeVisible();
        await expect(page.getByText('Performed By').last()).toBeVisible();
        
        // Close filters to remove any overlays
        await filtersButton.click();
        await page.waitForTimeout(500);

        // 5. Test opening Detail Modal (if rows exist)
        // Check if there are any rows with the "View Details" button
        const viewButtons = page.locator('button[title="View Details"]');
        const count = await viewButtons.count();
        
        if (count > 0) {
            // Click the first one
            await viewButtons.first().click();
            
            // Verify dialog opens
            const dialog = page.getByRole('dialog', { name: 'Audit Trail Detail' });
            await expect(dialog).toBeVisible();
            await expect(dialog.getByText('From State')).toBeVisible();
            await expect(dialog.getByText('To State')).toBeVisible();
            await expect(dialog.getByText('Entity Type')).toBeVisible();
            await expect(dialog.getByText('Entity ID')).toBeVisible();
            
            // Close dialog
            await page.keyboard.press('Escape');
            await expect(dialog).not.toBeVisible();
        }
    });

    test('can export audit trail data', async ({ page }) => {
        // Ensure we hit the dashboard first
        await expect(page).toHaveURL(/.*dashboard/);
        
        await page.goto('/pipeline-audit-trail');
        await page.waitForURL('**/pipeline-audit-trail', { timeout: 15000 });
        
        // Setup download listener before clicking
        const downloadPromise = page.waitForEvent('download', { timeout: 10000 });
        
        // Wait for export button and click
        const exportButton = page.locator('button').filter({ hasText: 'Export' });
        await expect(exportButton).toBeVisible();
        await exportButton.click();
        
        // Verify download starts and has correct extension
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toContain('pipeline_audit_trail_');
        expect(download.suggestedFilename()).toMatch(/\.(xlsx|csv)$/i);
    });
});
