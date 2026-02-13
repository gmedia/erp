import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Add', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should add a new asset model', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        await page.getByRole('button', { name: 'Add', exact: true }).click();

        const dialog = page.getByRole('dialog', { name: 'Add New Asset Model' });
        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('heading', { name: /Add New Asset Model/i })).toBeVisible();

        // Fill form
        await dialog.locator('input[name="model_name"]').fill('Test Model X1');
        await dialog.locator('input[name="manufacturer"]').fill('Test Manufacturer');
        
        // Select Category (AsyncSelect)
        await dialog.locator('button').filter({ hasText: /Select a category/i }).click();
        
        // Wait for search input if it exists
        const searchInput = page.getByPlaceholder('Search...').filter({ visible: true }).last();
        if (await searchInput.isVisible()) {
            await searchInput.fill('');
        }
        
        await page.getByRole('option').first().click();

        // Specs (JSON)
        await dialog.locator('textarea[name="specs"]').fill('{"ram": "16GB", "storage": "512GB SSD"}');

        // Submit
        await dialog.getByRole('button', { name: /Add/i, exact: true }).click();

        // Verify success
        // Dialog should close
        await expect(dialog).not.toBeVisible({ timeout: 15000 });
        
        // Verify in table
        await expect(page.locator('table')).toContainText('Test Model X1');
    });
});
