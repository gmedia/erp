import { test, expect } from '@playwright/test';
import { createBranch, searchBranch, login } from '../helpers';

test.describe('Branch Management - Search', () => {
  test('should search for a branch by name', async ({ page }) => {
    const branch1 = await createBranch(page);
    const branch2 = await createBranch(page);

    await searchBranch(page, branch1);
    await expect(page.locator(`tr:has-text("${branch1}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${branch2}")`)).not.toBeVisible();

    await searchBranch(page, branch2);
    await expect(page.locator(`tr:has-text("${branch2}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${branch1}")`)).not.toBeVisible();
  });
});
