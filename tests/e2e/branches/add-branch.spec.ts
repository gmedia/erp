import { test, expect } from '@playwright/test';
import { createBranch, searchBranch } from '../helpers';

test.describe('Branch Management - Add', () => {
  test('should add a new branch successfully', async ({ page }) => {
    const branchName = await createBranch(page);
    await searchBranch(page, branchName);
    const row = page.locator(`tr:has-text("${branchName}")`);
    await expect(row).toBeVisible();
  });
});
