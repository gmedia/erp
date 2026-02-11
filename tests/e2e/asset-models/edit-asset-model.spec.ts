import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Edit', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should edit an existing asset model', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        // Click on the first row or an action button to edit
        const firstRow = page.locator('tbody tr').first();
        const actionsButton = firstRow.getByRole('button', { name: 'Actions' });
        await actionsButton.click();

        const editButton = page.getByRole('menuitem', { name: 'Edit' });
        await editButton.click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Edit Asset Model' })).toBeVisible();

        // Update form
        const newModelName = `Updated Model ${Date.now()}`;
        await page.getByLabel('Model Name').fill(newModelName);
        
        // Update Specs
        await page.getByLabel('Specifications (JSON)').fill('{"updated": "true"}');

        // Submit
        await page.getByRole('button', { name: 'Update', exact: true }).click();

        // Verify success
        await expect(page.getByRole('dialog')).not.toBeVisible();

        // Verify in table
        await expect(page.locator('table')).toContainText(newModelName);
    });
});
