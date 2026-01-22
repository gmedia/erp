import { test, expect } from '@playwright/test';
import { createBranch, searchBranch } from '../helpers';

test('add new branch end‑to‑end', async ({ page }) => {
  // Create branch using shared helper (includes login & navigation)
  const name = await createBranch(page);

  // Search for the newly created branch
  await searchBranch(page, name);

  // Verify the branch appears in the table
  const row = page.locator(`text=${name}`);
  await expect(row).toBeVisible();
});
