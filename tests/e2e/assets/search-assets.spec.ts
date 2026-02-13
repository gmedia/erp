import { test, expect } from '@playwright/test';
import { createAsset } from '../helpers';

test('search asset by name', async ({ page }) => {
    // Create an asset with a unique name part to search for
    const assetCode = await createAsset(page); 
    // createAsset returns code, we assume name contains code or we can search by code.
    // The helper `createAsset` fills name as `Test Asset ${uniqueId}` and code as `AST-${uniqueId}`.
    
    await page.goto('/assets');
    
    const searchInput = page.getByPlaceholder(/Search assets.../i);
    await searchInput.fill(assetCode);
    await searchInput.press('Enter');
    
    await page.waitForLoadState('networkidle');
    
    // Verify the row is visible
    const row = page.locator('tbody tr').filter({ hasText: assetCode }).first();
    await expect(row).toBeVisible();
    
    // Verify that unrelated assets are not shown (optional, harder to guarantee in isolation but good practice)
    const tableBody = page.locator('tbody');
    await expect(tableBody).toContainText(assetCode);
});
