import { test, expect } from '@playwright/test';
import { createBranch, login } from '../helpers';

test('filter branches by search', async ({ page }) => {
  // Create a few branches with predictable names
  const pos1 = await createBranch(page);
  const pos2 = await createBranch(page);
  const pos3 = await createBranch(page, { name: 'SearchableBranch' + Date.now() });

  await page.goto('/branches');

  // Extract a partial match from pos3
  const partial = pos3.substring(0, 10);
  await page.fill('input[placeholder="Search branches..."]', partial);
  await page.press('input[placeholder="Search branches..."]', 'Enter');
  await page.waitForLoadState('networkidle');

  // Verify search results
  await expect(page.locator(`text=${pos3}`)).toBeVisible();
});
