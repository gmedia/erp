import { test, expect } from '@playwright/test';
import { createAsset } from '../helpers';

test('asset datatable checkbox behavior', async ({ page }) => {
    // Create a fresh asset to ensure data exists
    await createAsset(page);

    await page.goto('/assets');
    await page.waitForLoadState('networkidle');

    // 1. Check that the header does NOT have a checkbox
    // The header for selection is usually the first column. 
    // In createSelectColumn, header: () => null, so it should be empty or not contain a checkbox input.
    const headerFirstCell = page.locator('thead tr th').first();
    await expect(headerFirstCell).toBeVisible();
    await expect(headerFirstCell.locator('input[type="checkbox"]')).not.toBeVisible();

    // 2. Check that the body rows DO have checkboxes
    const firstRow = page.locator('tbody tr').first();
    await expect(firstRow).toBeVisible();
    
    const rowCheckbox = firstRow.locator('td').first().locator('button[role="checkbox"]');
    await expect(rowCheckbox).toBeVisible();

    // 3. Test selection
    await rowCheckbox.click();
    await expect(rowCheckbox).toHaveAttribute('aria-checked', 'true');
    
    // Check if row shows selected state (usually background color change)
    await expect(firstRow).toHaveAttribute('data-state', 'selected');
});
