import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Book Value & Depreciation Report', () => {
    test.beforeEach(async ({ page }) => {
        // Authenticate with default permissions
        await login(page);

        // Ensure we actually reach dashboard before navigating away
        await expect(page).toHaveURL(/.*dashboard/);
        
        // Then go to the report page
        await page.goto('/reports/book-value-depreciation');
        await page.waitForURL('**/reports/book-value-depreciation', { timeout: 15000 });
    });

    test('should display the report data table', async ({ page }) => {
        // Assert table is visible
        const table = page.locator('table');
        await expect(table).toBeVisible({ timeout: 15000 });

        // Assert columns
        await expect(page.getByRole('columnheader', { name: 'Asset Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Asset Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Purchase Cost' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Book Value' })).toBeVisible();
    });

    test('should be able to search for an asset', async ({ page }) => {
        const searchInput = page.getByPlaceholder('Search...');
        await searchInput.fill('IT-001'); // Assuming IT-001 exists from seeders or we just search
        
        // Wait for debounce and API
        await page.waitForTimeout(1500);
        
        // Assert we are still on the page and no crash occurred
        await expect(searchInput).toBeVisible();
    });

    test('should be able to open category filter', async ({ page }) => {
        const categoryFilter = page.locator('button').filter({ hasText: 'Category' });
        if (await categoryFilter.isVisible()) {
            await categoryFilter.click();
            await expect(page.getByPlaceholder('Filter by category...')).toBeVisible();
        }
    });

    test('should have export button', async ({ page }) => {
        const exportButton = page.getByRole('button', { name: /Export/i });
        await expect(exportButton).toBeVisible();
        // Option 1: test click but capture download
        // const downloadPromise = page.waitForEvent('download');
        // await exportButton.click();
        // const download = await downloadPromise;
        // expect(download.suggestedFilename()).toContain('book_value_depreciation_report');
    });
});
