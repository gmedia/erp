import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from '../helpers';

test('add new position end‑to‑end', async ({ page }) => {
  // Create position using shared helper (includes login & navigation)
  const name = await createPosition(page);

  // Search for the newly created position
  await searchPosition(page, name);

  // Verify the position appears in the table
  const row = page.locator(`text=${name}`);
  await expect(row).toBeVisible();
});