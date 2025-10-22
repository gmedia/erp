import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from '../helpers';

test('add multiple positions end‑to‑end', async ({ page }) => {
  // Create three positions, collecting their names
  const names: string[] = [];

  for (let i = 0; i < 3; i++) {
    const name = await createPosition(page);
    names.push(name);
  }

  // Verify each position appears in the list
  for (const name of names) {
    // Optionally ensure the position can be found via the search helper
    await searchPosition(page, name);
    await expect(page.locator(`text=${name}`)).toBeVisible();
  }
});