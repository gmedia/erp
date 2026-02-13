import { test, expect } from '@playwright/test';
import { login, createAssetModel, editAssetModel } from '../helpers';

test.describe('Asset Models - Edit', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should edit an asset model', async ({ page }) => {
        // Create an model to edit
        const modelName = await createAssetModel(page, {
            model_name: 'Model To Edit ' + Date.now()
        });

        // Update form
        const newModelName = `Updated Model ${Date.now()}`;
        await editAssetModel(page, modelName, {
            model_name: newModelName,
            specs: '{"updated": "true"}'
        });

        // Verify changes in the list
        await expect(page.locator('table')).toContainText(newModelName);
    });
});
