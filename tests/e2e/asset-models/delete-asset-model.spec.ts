import { test, expect } from '@playwright/test';
import { login, createAssetModel, searchAssetModel } from '../helpers';

test.describe('Asset Models - Delete', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should delete an asset model', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        // Create a temporary model to delete
        const modelName = await createAssetModel(page, {
            model_name: `Model To Delete ${Date.now()}`
        });
        
        await expect(page.locator('table')).toContainText(modelName);

        // Find the row and delete
        const row = page.locator('tbody tr', { hasText: modelName }).first();
        const actionsButton = row.getByRole('button', { name: /Actions/i });
        await actionsButton.click();

        const deleteButton = page.getByRole('menuitem', { name: /Delete/i });
        await deleteButton.click();

        // Confirm deletion
        const confirmDialog = page.getByRole('dialog').or(page.getByRole('alertdialog')).filter({ hasText: /Delete/i }).last();
        await expect(confirmDialog).toBeVisible();
        await confirmDialog.getByRole('button', { name: /Delete/i }).click();

        // Verify success
        await expect(page.locator('table')).not.toContainText(modelName);
    });
});
