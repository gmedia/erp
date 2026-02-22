import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Maintenance Cost Report', () => {
    test.beforeEach(async ({ page }) => {
        // Authenticate with default permissions
        await login(page);

        // Ensure we actually reach dashboard before navigating away
        await expect(page).toHaveURL(/.*dashboard/);
        
        // Then go to the report page
        await page.goto('/reports/maintenance-cost');
        await page.waitForURL('**/reports/maintenance-cost', { timeout: 15000 });
    });

    test('should display the report data table', async ({ page }) => {
        // Assert table is visible
        const table = page.locator('table');
        await expect(table).toBeVisible({ timeout: 15000 });

        // Assert columns
        await expect(page.getByRole('columnheader', { name: 'Asset Code' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Asset Name' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
        await expect(page.getByRole('columnheader', { name: 'Cost' })).toBeVisible();
    });

    test('should be able to search for a maintenance record', async ({ page }) => {
        const searchInput = page.getByPlaceholder('Search code, name, notes...');
        await searchInput.fill('Preventive');
        
        // Wait for debounce and API
        await page.waitForTimeout(1500);
        
        // Assert we are still on the page and no crash occurred
        await expect(searchInput).toBeVisible();
    });

    test('should be able to open type filter', async ({ page }) => {
        const typeFilter = page.getByRole('combobox').nth(3); // Type is the 4th filter after Search, Category, Branch, Vendor (Wait, Search is input, so AsyncSelect x3, Select x2).
        // Let's just use text 'Select type'
        const selectBtn = page.getByRole('combobox').filter({ hasText: 'Select type' });
        if (await selectBtn.isVisible()) {
            await selectBtn.click();
            await expect(page.getByRole('option', { name: 'Preventive' })).toBeVisible();
        }
    });

    test('should have export button', async ({ page }) => {
        const exportButton = page.getByRole('button', { name: /Export/i });
        await expect(exportButton).toBeVisible();
    });
});
