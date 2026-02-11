import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Delete', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should delete an asset model', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        // Create a temporary model to delete or use existing
        // Ideally we should create one to ensure test isolation
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        const modelName = `Model To Delete ${Date.now()}`;
        await page.getByLabel('Model Name').fill(modelName);
        
        // We need to fill category too
        await page.getByRole('combobox').click();
        await page.getByRole('option').first().click();
        
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();
        await expect(page.locator('table')).toContainText(modelName);

        // Find the row with the specific model name
        const row = page.locator('tbody tr', { hasText: modelName });
        const actionsButton = row.getByRole('button', { name: 'Actions' });
        await actionsButton.click();

        const deleteButton = page.getByRole('menuitem', { name: 'Delete' });
        await deleteButton.click();

        // Confirm deletion
        const confirmDialog = page.getByRole('alertdialog');
        await expect(confirmDialog).toBeVisible();
        await confirmDialog.getByRole('button', { name: 'Delete' }).click();

        // Verify success
        await expect(page.locator('table')).not.toContainText(modelName);
    });
});
