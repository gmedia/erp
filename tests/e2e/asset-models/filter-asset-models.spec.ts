import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Filter', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should filter by category', async ({ page }) => {
        // Create a model to filter
        await page.getByRole('button', { name: 'Add' }).click();
        await page.getByLabel('Model Name').fill('Filterable Model');
        await page.getByLabel('Manufacturer').fill('Filterable Mfg');
        await page.getByRole('combobox').click();
        const firstCategory = page.getByRole('option').first();
        const categoryName = await firstCategory.textContent(); // Capture category name if needed
        await firstCategory.click();
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();

        await expect(page.locator('table')).toBeVisible();

        // Open the filter dialog
        const filterButton = page.getByRole('button', { name: /Filters/i });
        await expect(filterButton).toBeVisible();
        await filterButton.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        
        // Find the category trigger inside the dialog
        // We find the div that contains the 'Category' label, then find the combobox within it
        const categoryTrigger = page.locator('div').filter({ has: page.getByText('Category', { exact: true }) }).getByRole('combobox');
        await expect(categoryTrigger).toBeVisible();
        await categoryTrigger.click();
        
        // Select an option from the popover
        // The options are rendered in a portal/popover, so we use page.getByRole('option')
        const option = page.getByRole('option').first();
        await expect(option).toBeVisible();
        await option.click();
        
        const filterResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.url().includes('asset_category_id') &&
            resp.status() === 200
        );
        await page.getByRole('button', { name: 'Apply Filters' }).click();
        await filterResponse;
    });
});
