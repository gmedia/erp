import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Approval Audit Trail', () => {
    test.beforeEach(async ({ page }) => {
        // Log in to gain session
        await login(page);
    });

    test('can view approval audit trail list and open detail modal', async ({ page }) => {
        // Ensure we hit the dashboard first
        await page.waitForURL('**/dashboard', { timeout: 15000 });
        
        // 1. Navigate to Approval Audit Trail page
        await page.goto('/approval-audit-trail');
        await page.waitForURL('**/approval-audit-trail', { timeout: 15000 });
        
        // 2. Verify page header
        await expect(page.getByText('Approval Audit Trail').last()).toBeVisible();

        // 3. Verify that the table renders (wait for API data to load)
        const table = page.locator('table');
        await expect(table).toBeVisible();
        await expect(page.getByText('Loading...')).not.toBeVisible();
        
        // Check table headers
        await expect(page.getByRole('columnheader', { name: 'Date' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Document Type' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Document ID' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Event' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Actor' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Step' })).toBeVisible();

        // 4. Test filter presence
        await expect(page.getByPlaceholder('Search IP, Document ID, user...')).toBeVisible();
        
        // Open the filters collapsible if it's not open
        const filtersButton = page.getByRole('button', { name: 'Filters' });
        await expect(filtersButton).toBeVisible();
        await filtersButton.click();
        await page.waitForTimeout(500); // Wait for transition
        
        await page.getByRole('dialog').or(page.locator('.filter-container').first());
        await expect(page.getByText('Document Type').last()).toBeVisible();
        await expect(page.getByText('Event').last()).toBeVisible();
        await expect(page.getByText('Actor').last()).toBeVisible();
        
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
            await expect(dialog.getByText('Event').first()).toBeVisible();
            await expect(dialog.getByText('Document Type')).toBeVisible();
            await expect(dialog.getByText('Actor')).toBeVisible();
            await expect(dialog.getByText('Metadata Snapshot')).toBeVisible();
            
            // Close dialog
            await page.keyboard.press('Escape');
            await expect(dialog).not.toBeVisible();
        }
    });

    test('can export audit trail data', async ({ page }) => {
        // Ensure we hit the dashboard first
        await page.waitForURL('**/dashboard', { timeout: 15000 });
        
        await page.goto('/approval-audit-trail');
        await page.waitForURL('**/approval-audit-trail', { timeout: 15000 });
        
        // Setup download listener before clicking
        const downloadPromise = page.waitForEvent('download', { timeout: 10000 });
        
        // Wait for export button and click
        const exportButton = page.locator('button').filter({ hasText: 'Export' });
        await expect(exportButton).toBeVisible();
        await exportButton.click();
        
        // Verify download starts and has correct extension
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toContain('approval_audit_trail_');
        expect(download.suggestedFilename()).toMatch(/\.(xlsx|csv)$/i);
    });
});
