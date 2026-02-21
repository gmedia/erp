import { test, expect } from '@playwright/test';
import { createAssetStocktake, navigateToPerformStocktake } from './helpers';
import { login } from '../helpers';

test.describe('Asset Stocktake Items', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-stocktakes');
    });

    test('can perform asset stocktake and sync items', async ({ page }) => {
        // 1. Create a stocktake
        const reference = await createAssetStocktake(page);

        // 2. Navigate to Perform Page
        await navigateToPerformStocktake(page, reference);

        // 3. Wait for items to load in the table
        // Should show "No assets expected" if no active assets, but let's test the save functionality anyway
        await expect(page.locator('[data-slot="card-title"]', { hasText: /Perform Asset Stocktake/i })).toBeVisible({ timeout: 15000 });
        await expect(page.getByText(reference).first()).toBeVisible();

        // 4. Check if there are items or empty state
        await expect(
            page.locator('tbody tr').first()
        ).toBeVisible({ timeout: 15000 });

        const rows = page.locator('tbody tr');
        const rowCount = await rows.count();
        const hasItems = rowCount > 0 && !(await page.getByText(/no assets expected/i).isVisible());

        if (hasItems) {
            // Fill 'Found' for all rows
            for (let i = 0; i < rowCount; i++) {
                const row = rows.nth(i);
                await row.getByRole('combobox').first().click();
                await page.getByRole('option', { name: 'Found' }).click();
            }

            // Notes for first row
            await rows.first().getByPlaceholder(/notes/i).fill('Found in good condition during E2E test');
            
            // Save
            await page.getByRole('button', { name: /save stocktake items/i }).click();
            
            // Wait for toast
            await expect(page.getByText(/stocktake items saved successfully/i)).toBeVisible();
        } else {
            // If no items, the save button might be disabled, or we skip
            await expect(page.getByText(/no assets expected/i)).toBeVisible();
            await expect(page.getByRole('button', { name: /save stocktake items/i })).toBeDisabled();
        }
    });
});
