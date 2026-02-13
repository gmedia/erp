import { test, expect } from '@playwright/test';
import { login, createAssetModel, searchAssetModel } from '../helpers';

test.describe('Asset Models - Search', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should search by model name', async ({ page }) => {
        // Create a specific model
        const modelName = 'Apple MacBook Pro';
        await createAssetModel(page, { model_name: modelName, manufacturer: 'Apple' });

        // Search
        await searchAssetModel(page, 'Apple MacBook Pro');

        // Verify
        await expect(page.locator('tbody tr').first()).toContainText('Apple MacBook Pro');
    });

    test('should search by manufacturer', async ({ page }) => {
        // Create a model to search for
        const modelName = 'Searchable Model ' + Date.now();
        await createAssetModel(page, { model_name: modelName, manufacturer: 'Toyota Searchable' });

        // Search by Model Name
        await searchAssetModel(page, modelName);
        await expect(page.locator('tbody tr').first()).toContainText(modelName);
        
        // Search by Manufacturer
        await searchAssetModel(page, 'Toyota Searchable');
        await expect(page.locator('tbody tr').first()).toContainText(modelName);
    });
});
