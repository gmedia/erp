import { test, expect } from '@playwright/test';
import { createAssetLocation, login } from '../helpers';

test.describe('Asset Location Filters', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('filter asset locations by branch', async ({ page }) => {
        const name = await createAssetLocation(page);
        
        await page.goto('/asset-locations');

        // Find the branch from the row we just created
        const row = page.locator('tr').filter({ hasText: name }).first();
        const branchCell = row.locator('td').nth(3);
        const branchName = await branchCell.innerText();

        // Open filter dialog
        await page.getByRole('button', { name: /Filters/i }).click();
        const dialog = page.getByRole('dialog', { name: /Filters/i });
        await expect(dialog).toBeVisible();

        // Find Branch filter trigger - it's a combobox or button with the placeholder
        const branchTrigger = dialog.getByRole('combobox').filter({ hasText: /All Branches|Branch/i }).first();
        await expect(branchTrigger).toBeVisible();
        await branchTrigger.click();
        
        // Select the branch name from the options
        const option = page.getByRole('option', { name: branchName.trim(), exact: true }).first();
        await option.click();

        // Apply filters
        await page.getByRole('button', { name: 'Apply' }).click();
        await expect(dialog).not.toBeVisible();
        await page.waitForLoadState('networkidle');

        // Verify the table rows
        const rows = page.locator('tbody tr');
        const count = await rows.count();
        expect(count).toBeGreaterThan(0);
        
        for (let i = 0; i < count; i++) {
            await expect(rows.nth(i).locator('td').nth(3)).toContainText(branchName.trim());
        }
    });

    test('filter asset locations by parent location', async ({ page }) => {
        // Create a parent location
        const parentName = await createAssetLocation(page);
        
        await page.goto('/asset-locations');

        // Create a child location under this parent using the UI for maximum E2E coverage
        await page.getByRole('button', { name: /Add/i }).first().click();
        const createDialog = page.getByRole('dialog', { name: /Add New Asset Location/i });
        await expect(createDialog).toBeVisible();

        const childName = `Child-${Date.now()}`;
        await createDialog.locator('input[name="code"]').fill(`C-${Date.now()}`);
        await createDialog.locator('input[name="name"]').fill(childName);

        // Select any branch (required)
        await createDialog.locator('button').filter({ hasText: /Select a branch/i }).click();
        await page.getByRole('option').first().click();

        // Select our created parent
        await createDialog.locator('button').filter({ hasText: /Select a parent/i }).click();
        await page.getByRole('option', { name: parentName, exact: true }).click();

        // Save
        await createDialog.getByRole('button', { name: /Add/i }).last().click();
        await expect(createDialog).not.toBeVisible({ timeout: 15000 });

        // Now filter by that parent
        await page.getByRole('button', { name: /Filters/i }).click();
        const filterDialog = page.getByRole('dialog', { name: /Filters/i });
        await expect(filterDialog).toBeVisible();

        const filterTrigger = filterDialog.getByRole('combobox').filter({ hasText: /All Locations|Parent Location/i }).first();
        await expect(filterTrigger).toBeVisible();
        await filterTrigger.click();
        await page.getByRole('option', { name: parentName, exact: true }).click();

        await page.getByRole('button', { name: 'Apply' }).click();
        await expect(filterDialog).not.toBeVisible();
        await page.waitForLoadState('networkidle');

        // Verify
        const rows = page.locator('tbody tr');
        const count = await rows.count();
        expect(count).toBeGreaterThan(0);
        
        for (let i = 0; i < count; i++) {
            await expect(rows.nth(i).locator('td').nth(4)).toContainText(parentName);
        }
    });
});
