import { test, expect } from '@playwright/test';
import { createBranch, editBranch, searchBranch } from '../helpers';

test.describe('Branch Management - Edit', () => {
  test('should edit an existing branch successfully', async ({ page }) => {
    const branchName = await createBranch(page);
    const updatedName = `${branchName} Updated`;

    await editBranch(page, branchName, { name: updatedName });

    // Verify the update
    await searchBranch(page, updatedName);
    const row = page.locator(`tr:has-text("${updatedName}")`);
    await expect(row).toBeVisible();
  });
});
