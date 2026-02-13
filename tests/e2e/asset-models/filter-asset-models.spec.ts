import { test, expect } from '@playwright/test';
import { login, createAssetModel, searchAssetModel } from '../helpers';

test.describe('Asset Models - Filter', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should filter by category', async ({ page }) => {
        // Create a model in a specific category
        const modelName = await createAssetModel(page, {
            model_name: `Filterable Model ${Date.now()}`
        });

        // Search for it to ensure it's in the list
        await searchAssetModel(page, modelName);

        // Get the category of the created model
        const row = page.locator('tbody tr', { hasText: modelName }).first();
        const categoryName = await row.locator('td').nth(3).innerText();

        // Open Filter Dialog
        const filterTrigger = page.getByRole('button', { name: /Filters/i });
        await filterTrigger.click();

        const filterDialog = page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: /Filters/i }) });
        await expect(filterDialog).toBeVisible();

        // Select category in filter
        const categoryTrigger = filterDialog.locator('button').filter({ hasText: /All Categories|Select a category/i }).first();
        await categoryTrigger.click();
        
        await page.getByRole('option', { name: categoryName }).first().click();

        // Apply
        await filterDialog.getByRole('button', { name: /Apply/i }).click();
        
        // Verify results
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });
});
