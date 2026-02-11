import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - View', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should view asset model details', async ({ page }) => {
        // Create a model to view
        const modelName = 'Viewable Model ' + Date.now();
        await page.getByRole('button', { name: 'Add' }).click();
        await page.getByLabel('Model Name').fill(modelName);
        await page.getByLabel('Manufacturer').fill('Viewable Mfg');
        await page.getByRole('combobox').click();
        await page.getByRole('option').first().click();
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();

        // Search for it
        const searchInput = page.getByPlaceholder('Search by model name or manufacturer...');
        await searchInput.fill(modelName);
        
        const searchResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            (resp.url().includes('search=') || resp.request().url().includes('search=')) && // Check request or response URL
            resp.status() === 200
        );
        await searchInput.press('Enter');
        await searchResponse;

        // Click on the row action
        const row = page.locator('tr', { hasText: modelName }).first();
        await expect(row).toBeVisible();
        const actionsButton = row.getByRole('button', { name: 'Actions' });
        await actionsButton.click();

        const viewButton = page.getByRole('menuitem', { name: /View/i });
        await viewButton.click();

        // Expect a modal
        await expect(page.getByRole('dialog')).toBeVisible();
        // The DialogTitle renders the model_name
        await expect(page.getByRole('heading', { name: modelName })).toBeVisible();

        // Check for content
        const dialog = page.getByRole('dialog');
        await expect(dialog.getByText(modelName).first()).toBeVisible();
        await expect(dialog.getByText('Viewable Mfg')).toBeVisible();
    });
});
