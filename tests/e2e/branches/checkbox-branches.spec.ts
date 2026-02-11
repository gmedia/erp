import { test, expect } from '@playwright/test';
import { createBranch, login } from '../helpers';

test.describe('Branch Management - Checkboxes', () => {
  test('should show checkboxes on rows but NOT on header', async ({ page }) => {
    await createBranch(page);
    await page.goto('/branches');

    // Row checkbox should be visible
    // We wait for at least one row
    const firstRowCheckbox = page.locator('tbody tr td input[type="checkbox"]').first(); // Or generic checkbox selector since shadcn uses valid checkbox
    // Shadcn checkbox usually is a button acting as checkbox or input[type=checkbox] hidden.
    // Helper `createSelectColumn` uses `Checkbox` component which usually renders a button with role='checkbox'
    
    // Wait for data
    await page.waitForSelector('tbody tr');

    const rowCheckbox = page.locator('tbody tr [role="checkbox"]').first();
    await expect(rowCheckbox).toBeVisible();

    // Header checkbox should NOT be visible
    // The header for select column returns null, so no checkbox should be in the first header cell or any header cell.
    // We can check the first header cell explicitly.
    const headerCheckbox = page.locator('thead tr th [role="checkbox"]');
    await expect(headerCheckbox).not.toBeVisible();
  });
});
