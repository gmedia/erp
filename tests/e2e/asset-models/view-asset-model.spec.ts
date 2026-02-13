import { test, expect } from '@playwright/test';
import { login, createAssetModel, searchAssetModel } from '../helpers';

test.describe('Asset Models - View', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should view asset model details', async ({ page }) => {
        // Create a model to view
        const modelName = await createAssetModel(page, {
            model_name: 'Viewable Model ' + Date.now(),
            manufacturer: 'Viewable Mfg'
        });

        // Search for it
        await searchAssetModel(page, modelName);

        // Click on the row action
        const row = page.locator('tbody tr', { hasText: modelName }).first();
        await expect(row).toBeVisible();
        const actionsButton = row.getByRole('button', { name: /Actions/i });
        await actionsButton.click();

        const viewButton = page.getByRole('menuitem', { name: /View/i });
        await viewButton.click();

        // Expect a modal
        const viewDialog = page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: modelName }) });
        await expect(viewDialog).toBeVisible();

        // Check for content
        await expect(viewDialog.getByText(modelName).first()).toBeVisible();
        await expect(viewDialog.getByText('Viewable Mfg')).toBeVisible();
    });
});
