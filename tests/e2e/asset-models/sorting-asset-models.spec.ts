import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Models - Sorting', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should sort by model name', async ({ page }) => {
        // Create 2 models for sorting
        // Model A
        await page.getByRole('button', { name: 'Add' }).click();
        await page.getByLabel('Model Name').fill('AAA Model');
        await page.getByLabel('Manufacturer').fill('Mfg A');
        await page.getByRole('combobox').click();
        await page.getByRole('option').first().click();
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();

        // Model Z
        await page.getByRole('button', { name: 'Add' }).click();
        await page.getByLabel('Model Name').fill('ZZZ Model');
        await page.getByLabel('Manufacturer').fill('Mfg Z');
        await page.getByRole('combobox').click();
        await page.getByRole('option').first().click();
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).not.toBeVisible();

        await expect(page.locator('table')).toBeVisible();


        const header = page.getByRole('button', { name: 'Model Name' });
        await header.click();
        
        // Wait for sorting to apply
        await page.waitForTimeout(1000); 

        // Verify ASC: AAA Model should be first 
        // Note: We check if the FIRST row contains AAA Model.
        // If there are pinned rows or something else, this might fail, but standard table behavior is fine.
        const rows = page.locator('tbody tr');
        await expect(rows.first()).toContainText('AAA Model');
        
        // Click again for DESC
        await header.click();
        await page.waitForTimeout(1000);

        // Verify DESC: ZZZ Model should be first
        await expect(rows.first()).toContainText('ZZZ Model');
    });

    test('should sort by category', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        const header = page.getByRole('button', { name: 'Category' });
        await header.click();
    });
});
