import { test, expect } from '@playwright/test';
import { createBranch, login } from '../helpers';

test.describe('Branch Management - Filter', () => {
  test('should filter branches', async ({ page }) => {
    // Currently Branch only has Search filter. 
    // This test verifies that the filter bar is present and functional.
    const branchName = await createBranch(page);
    
    await login(page);
    await page.goto('/branches');

    const searchInput = page.getByPlaceholder(/Search branches.../i);
    await expect(searchInput).toBeVisible();

    await searchInput.fill(branchName);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${branchName}")`)).toBeVisible();
  });

  test('should reset filters', async ({ page }) => {
     await login(page);
     await page.goto('/branches');
     
     const searchInput = page.getByPlaceholder(/Search branches.../i);
     await searchInput.fill('NonExistentBranch');
     await searchInput.press('Enter');

     await expect(page.getByText('No results.')).toBeVisible();

     // Click Reset
     const resetButton = page.getByRole('button', { name: /Reset/i });
     if (await resetButton.isVisible()) {
         await resetButton.click();
         await expect(page.getByText('No results.')).not.toBeVisible();
     }
  });
});
