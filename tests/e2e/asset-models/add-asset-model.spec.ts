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

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Add New Asset Model' })).toBeVisible();

        // Fill form
        await page.getByLabel('Model Name').fill('Test Model X1');
        await page.getByLabel('Manufacturer').fill('Test Manufacturer');
        
        // Select Category (AsyncSelect)
        await page.getByRole('combobox').click();
        // Wait for options to load and select one. 
        // Assuming there are seeded categories. If not, we might need to seed or mock.
        // For now, let's assume we can type to search or just pick the first one.
        // If AsyncSelect loads, it usually shows "Type to search..." or options.
        // We can try to select the first available option.
        await page.getByRole('option').first().click();

        // Specs (JSON)
        await page.getByLabel('Specifications (JSON)').fill('{"ram": "16GB", "storage": "512GB SSD"}');

        // Submit
        await page.getByRole('button', { name: 'Add', exact: true }).click();

        // Verify success
        // Dialog should close
        await expect(page.getByRole('dialog')).not.toBeVisible();
        
        // Toast or notification?
        // await expect(page.getByText('Asset model created successfully')).toBeVisible();

        // Verify in table
        await expect(page.locator('table')).toContainText('Test Model X1');
    });
});
