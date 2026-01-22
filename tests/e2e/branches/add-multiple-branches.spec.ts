import { test, expect } from '@playwright/test';
import { createBranch, searchBranch } from '../helpers';

test('add multiple branches end‑to‑end', async ({ page }) => {
  // Create three branches, collecting their names
  const names: string[] = [];

  for (let i = 0; i < 3; i++) {
    const name = await createBranch(page);
    names.push(name);
  }

  // Verify each branch appears in the list
  for (const name of names) {
    // Optionally ensure the branch can be found via the search helper
    await searchBranch(page, name);
    await expect(page.locator(`text=${name}`)).toBeVisible();
  }
});
