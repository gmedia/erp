import { test, expect } from '@playwright/test';
import { createBranch, searchBranch, editBranch } from '../helpers';

test('edit branch end‑to‑end', async ({ page }) => {
  // Create a new branch using shared helper (includes login & navigation)
  const name = await createBranch(page);

  // Optionally search for the branch before editing (helper also searches internally)
  await searchBranch(page, name);

  // Edit the branch using shared helper
  const updatedName = name + ' Updated';
  await editBranch(page, name, { name: updatedName });

  // Verify the updated name appears in the table
  await expect(page.locator('text=' + updatedName)).toBeVisible();
});
